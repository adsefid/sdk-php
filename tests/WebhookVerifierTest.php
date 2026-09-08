<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Exceptions\AdsefidWebhookVerificationException;
use Adsefid\Sdk\Tests\Support\Fixtures;
use Adsefid\Sdk\Tests\Support\WebhookSigner;
use Adsefid\Sdk\Webhooks\MessengerStatusWebhookEvent;
use Adsefid\Sdk\Webhooks\ReceiveWebhookEvent;
use Adsefid\Sdk\Webhooks\StatusWebhookEvent;
use Adsefid\Sdk\Webhooks\WebhookEventTypes;
use Adsefid\Sdk\Webhooks\WebhookHeaders;
use Adsefid\Sdk\Webhooks\WebhookVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WebhookVerifierTest extends TestCase
{
    private const A_CENTURY = 100 * 365 * 24 * 60 * 60;

    /**
     * @return array{secret: string, timestamp: string, body_file: string, signature: string, tampered_signature: string}
     */
    private static function vector(): array
    {
        /** @var array{secret: string, timestamp: string, body_file: string, signature: string, tampered_signature: string} $vector */
        $vector = Fixtures::json('webhooks/signature_vector.json');

        return $vector;
    }

    private static function goldenBody(): string
    {
        return Fixtures::bytes(self::vector()['body_file']);
    }

    /**
     * The cross-SDK vector. It proves the HMAC key is the Base64-DECODED secret
     * bytes rather than the UTF-8 bytes of the Base64 string the panel shows.
     * Its timestamp is fixed, so the staleness window has to be opened wide.
     */
    public function testTheGoldenVectorVerifies(): void
    {
        $vector = self::vector();
        $verifier = new WebhookVerifier($vector['secret']);

        $event = $verifier->verifyAndParse(
            self::goldenBody(),
            $vector['signature'],
            $vector['timestamp'],
            self::A_CENTURY,
        );

        self::assertInstanceOf(ReceiveWebhookEvent::class, $event);
        self::assertSame(WebhookEventTypes::RECEIVE, $event->type);
        self::assertSame(1, $event->attempt);
        self::assertSame('1', $event->version);
        self::assertCount(1, $event->data);
        self::assertNotSame('', $event->data[0]['sender']);
    }

    /**
     * Regression guard for the key-derivation fix: keying the HMAC with the
     * text of the Base64 secret is what this SDK used to do, and it never
     * matched a real delivery.
     */
    public function testASignatureKeyedWithTheBase64TextIsRejected(): void
    {
        $vector = self::vector();
        $wrong = 'v1=' . base64_encode(hash_hmac(
            'sha256',
            $vector['timestamp'] . '.' . self::goldenBody(),
            $vector['secret'],
            true,
        ));

        self::assertNotSame($vector['signature'], $wrong, 'the test vector is degenerate');

        $this->expectException(AdsefidWebhookVerificationException::class);
        (new WebhookVerifier($vector['secret']))
            ->verifyAndParse(self::goldenBody(), $wrong, $vector['timestamp'], self::A_CENTURY);
    }

    public function testFromKeyAcceptsAnAlreadyDecodedKey(): void
    {
        $vector = self::vector();
        $key = base64_decode($vector['secret'], true);
        self::assertIsString($key);

        $event = WebhookVerifier::fromKey($key)->verifyAndParse(
            self::goldenBody(),
            $vector['signature'],
            $vector['timestamp'],
            self::A_CENTURY,
        );

        self::assertInstanceOf(ReceiveWebhookEvent::class, $event);
    }

    public function testASecretThatIsNotBase64IsRejected(): void
    {
        $this->expectException(AdsefidWebhookVerificationException::class);
        new WebhookVerifier('not base64 !!');
    }

    /**
     * @return iterable<string, array{string, class-string}>
     */
    public static function eventTypeProvider(): iterable
    {
        yield 'receive' => ['webhooks/receive.body.json', ReceiveWebhookEvent::class];
        yield 'status' => ['webhooks/status.body.json', StatusWebhookEvent::class];
        yield 'messenger status' => ['webhooks/messenger_status.body.json', MessengerStatusWebhookEvent::class];
    }

    /**
     * @param class-string $expectedClass
     */
    #[DataProvider('eventTypeProvider')]
    public function testEachEventTypeParsesToItsOwnClass(string $fixture, string $expectedClass): void
    {
        $vector = self::vector();
        $body = Fixtures::bytes($fixture);
        $timestamp = WebhookSigner::now();

        $event = (new WebhookVerifier($vector['secret']))->verifyAndParse(
            $body,
            WebhookSigner::sign($vector['secret'], $timestamp, $body),
            $timestamp,
        );

        self::assertInstanceOf($expectedClass, $event);
    }

    public function testStatusItemsCarryATypedDeliveryStatus(): void
    {
        $vector = self::vector();
        $body = Fixtures::bytes('webhooks/status.body.json');
        $timestamp = WebhookSigner::now();

        $event = (new WebhookVerifier($vector['secret']))->verifyAndParse(
            $body,
            WebhookSigner::sign($vector['secret'], $timestamp, $body),
            $timestamp,
        );

        self::assertInstanceOf(StatusWebhookEvent::class, $event);
        self::assertSame(WebServiceMessageStatus::Delivered, $event->data[0]['status_delivery']);
        self::assertSame('order-10001', $event->data[0]['local_id']);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function rejectionProvider(): iterable
    {
        $vector = self::vector();
        $body = Fixtures::bytes($vector['body_file']);
        $timestamp = WebhookSigner::now();
        $signature = WebhookSigner::sign($vector['secret'], $timestamp, $body);

        yield 'tampered signature' => [$body, $vector['tampered_signature'], $vector['timestamp']];
        yield 'tampered body' => [$body . ' ', $signature, $timestamp];
        yield 'missing v1= prefix' => [$body, substr($signature, 3), $timestamp];
        yield 'signature is not base64' => [$body, 'v1=not-base-64-!!', $timestamp];
        yield 'non-numeric timestamp' => [$body, $signature, 'not-a-number'];
        yield 'empty timestamp' => [$body, $signature, ''];

        $stale = WebhookSigner::now(-301);
        yield 'stale timestamp' => [$body, WebhookSigner::sign($vector['secret'], $stale, $body), $stale];

        $future = WebhookSigner::now(301);
        yield 'future timestamp' => [$body, WebhookSigner::sign($vector['secret'], $future, $body), $future];
    }

    /**
     * Assert the exception type only, never the message: a doubly-invalid
     * request may report either failure depending on the order of checks.
     */
    #[DataProvider('rejectionProvider')]
    public function testRejections(string $body, string $signature, string $timestamp): void
    {
        $this->expectException(AdsefidWebhookVerificationException::class);
        (new WebhookVerifier(self::vector()['secret']))->verifyAndParse($body, $signature, $timestamp);
    }

    public function testAWrongSecretIsRejected(): void
    {
        $vector = self::vector();
        $body = self::goldenBody();
        $timestamp = WebhookSigner::now();
        $signature = WebhookSigner::sign($vector['secret'], $timestamp, $body);

        $this->expectException(AdsefidWebhookVerificationException::class);
        (new WebhookVerifier(base64_encode('a-completely-different-32-byte!!!')))
            ->verifyAndParse($body, $signature, $timestamp);
    }

    public function testMaxAgeSecondsWidensTheWindow(): void
    {
        $vector = self::vector();
        $body = self::goldenBody();
        $timestamp = WebhookSigner::now(-600);
        $signature = WebhookSigner::sign($vector['secret'], $timestamp, $body);
        $verifier = new WebhookVerifier($vector['secret']);

        $event = $verifier->verifyAndParse($body, $signature, $timestamp, 900);
        self::assertInstanceOf(ReceiveWebhookEvent::class, $event);

        $this->expectException(AdsefidWebhookVerificationException::class);
        $verifier->verifyAndParse($body, $signature, $timestamp);
    }

    /** All five SDKs report the signature first for a doubly-invalid request. */
    public function testTheSignatureIsCheckedBeforeFreshness(): void
    {
        $vector = self::vector();

        $this->expectExceptionMessageMatches('/signature/i');
        (new WebhookVerifier($vector['secret']))->verifyAndParse(
            self::goldenBody(),
            $vector['tampered_signature'],
            WebhookSigner::now(-600),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unparseablePayloadProvider(): iterable
    {
        yield 'unknown event type' => [Fixtures::bytes('webhooks/unknown_type.body.json')];
        yield 'not json' => ['<html>nope</html>'];
        yield 'json but not an object' => ['[1,2,3]'];
        yield 'missing required fields' => ['{"type":"receive"}'];
    }

    #[DataProvider('unparseablePayloadProvider')]
    public function testUnparseablePayloadsAreRejected(string $body): void
    {
        $vector = self::vector();
        $timestamp = WebhookSigner::now();

        $this->expectException(AdsefidWebhookVerificationException::class);
        (new WebhookVerifier($vector['secret']))->verifyAndParse(
            $body,
            WebhookSigner::sign($vector['secret'], $timestamp, $body),
            $timestamp,
        );
    }

    public function testHeaderConstantsAreTheWireProtocol(): void
    {
        // Renaming any of these breaks every deployed receiver.
        self::assertSame('X-Atlas-Webhook-Id', WebhookHeaders::ID);
        self::assertSame('X-Atlas-Webhook-Signature', WebhookHeaders::SIGNATURE);
        self::assertSame('X-Atlas-Webhook-Timestamp', WebhookHeaders::TIMESTAMP);
        self::assertSame('X-Atlas-Webhook-Event', WebhookHeaders::EVENT);
        self::assertSame('X-Atlas-Webhook-Attempt', WebhookHeaders::ATTEMPT);
    }

    public function testServerHeaderVariantsMatchThePhpSuperglobalNaming(): void
    {
        // $_SERVER upper-cases, replaces dashes and prefixes HTTP_.
        self::assertSame('HTTP_X_ATLAS_WEBHOOK_SIGNATURE', WebhookHeaders::SERVER_SIGNATURE);
        self::assertSame('HTTP_X_ATLAS_WEBHOOK_TIMESTAMP', WebhookHeaders::SERVER_TIMESTAMP);
    }

    public function testEventTypeConstants(): void
    {
        self::assertSame('receive', WebhookEventTypes::RECEIVE);
        self::assertSame('status', WebhookEventTypes::STATUS);
        self::assertSame('messenger.status', WebhookEventTypes::MESSENGER_STATUS);
    }
}
