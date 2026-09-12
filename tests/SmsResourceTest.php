<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Enums\WebServiceResponseCode;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Models\Sms\BulkSmsReceptor;
use Adsefid\Sdk\Models\Sms\CancelSmsRequest;
use Adsefid\Sdk\Models\Sms\GetReceivedSmsRequest;
use Adsefid\Sdk\Models\Sms\GetSmsStatusRequest;
use Adsefid\Sdk\Models\Sms\P2PSmsMessage;
use Adsefid\Sdk\Models\Sms\SendBulkSmsRequest;
use Adsefid\Sdk\Models\Sms\SendP2PSmsRequest;
use Adsefid\Sdk\Models\Sms\SendSingleSmsRequest;
use Adsefid\Sdk\Models\Sms\SendTemplateSmsRequest;
use Adsefid\Sdk\Tests\Support\Fixtures;
use Adsefid\Sdk\Tests\Support\TestClient;
use PHPUnit\Framework\TestCase;

final class SmsResourceTest extends TestCase
{
    public function testSendSingleBuildsTheRequest(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/sms.send_single.success.json');

        $client->sms->sendSingle(new SendSingleSmsRequest(
            receptor: '98912xxxxxxx',
            lineNumber: '3000xxxx',
            message: 'hello',
        ));

        $request = $http->only();
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/v1/sms/single', $request->getUri()->getPath());
        self::assertSame(TestClient::API_KEY, $request->getHeaderLine('X-API-KEY'));
        self::assertStringStartsWith('adsefid-php/', $request->getHeaderLine('User-Agent'));
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));

        $body = json_decode((string) $request->getBody(), true);
        self::assertSame(
            ['receptor' => '98912xxxxxxx', 'line_number' => '3000xxxx', 'message' => 'hello', 'hide' => false],
            $body,
        );
        // An omitted optional is absent, not null.
        foreach (['send_time', 'local_id', 'line_selector'] as $absent) {
            self::assertArrayNotHasKey($absent, $body);
        }
    }

    public function testSendSinglePassesSuppliedOptionals(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/sms.send_single.success.json');

        $client->sms->sendSingle(new SendSingleSmsRequest(
            receptor: '98912xxxxxxx',
            lineNumber: '3000xxxx',
            message: 'hello',
            lineSelector: LineSelector::BulkServiceSendBased,
            sendTime: new \DateTimeImmutable('2026-04-04T11:00:00+03:30'),
            localId: 'order-1',
            hide: true,
        ));

        $body = json_decode((string) $http->only()->getBody(), true);
        self::assertSame('order-1', $body['local_id']);
        self::assertTrue($body['hide']);
        self::assertSame(2, $body['line_selector']);
        self::assertStringStartsWith('2026-04-04T11:00:00', $body['send_time']);
    }

    public function testSendSingleParsesTheResponse(): void
    {
        [$client] = TestClient::respondingWithFixture('envelopes/sms.send_single.success.json');

        $result = $client->sms->sendSingle(new SendSingleSmsRequest(
            receptor: '98912xxxxxxx',
            lineNumber: '3000xxxx',
            message: 'hi',
        ));

        self::assertSame(WebServiceMessageStatus::Scheduled, $result->status);
        self::assertSame(1, $result->segmentCount);
        self::assertSame(120.0, $result->cost);
        self::assertNotNull($result->sendTime);
    }

    public function testBulkPartialSuccessIsNotAnError(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/sms.send_bulk.partial_success.json');

        $result = $client->sms->sendBulk(new SendBulkSmsRequest(
            receptors: [new BulkSmsReceptor('a'), new BulkSmsReceptor('', '-bad')],
            message: 'm',
            lineNumber: '3000xxxx',
        ));

        self::assertCount(2, $result->receptors);
        self::assertSame(1000, $result->receptors[0]->statusCode);
        self::assertSame(WebServiceMessageStatus::Scheduled, $result->receptors[0]->messageStatus);
        self::assertNull($result->receptors[0]->errorCode);
        // 2025 RECEPTOR_BLACKLISTED is a response code, not a message status —
        // which is why the raw int is kept alongside the typed views.
        self::assertSame(2025, $result->receptors[1]->statusCode);
        self::assertNull($result->receptors[1]->messageStatus);
        self::assertSame(WebServiceResponseCode::ReceptorBlacklisted, $result->receptors[1]->errorCode);
        self::assertNull($result->receptors[1]->messageId);
        self::assertSame(2, $result->totalCount);
        self::assertCount(2, json_decode((string) $http->only()->getBody(), true)['receptors']);
    }

    public function testP2pPartialSuccessIsNotAnError(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/sms.send_p2p.partial_success.json');

        $result = $client->sms->sendP2P(new SendP2PSmsRequest(
            messages: [new P2PSmsMessage('a', 'x'), new P2PSmsMessage('', '')],
            lineNumber: '3000xxxx',
        ));

        self::assertSame([1000, 2014], array_map(static fn ($item) => $item->statusCode, $result->messages));
        self::assertSame(WebServiceResponseCode::InvalidReceptor, $result->messages[1]->errorCode);
        self::assertCount(2, json_decode((string) $http->only()->getBody(), true)['messages']);
    }

    /**
     * A status code this SDK does not know yet must not fail the call: the raw
     * value stays readable and the typed view is simply null.
     */
    public function testAnUnknownStatusCodeIsCarriedThroughNotRejected(): void
    {
        $body = Fixtures::json('envelopes/sms.send_single.success.json');
        $body['data']['status'] = 1998;
        [$client] = TestClient::respondingWith(json_encode($body, JSON_THROW_ON_ERROR));

        $result = $client->sms->sendSingle(new SendSingleSmsRequest('a', '3000', 'm'));

        self::assertSame(1998, $result->statusCode);
        self::assertNull($result->status);
    }

    /**
     * A number-typed parameter sent as a string keeps its exact digits: the
     * platform substitutes such a value verbatim.
     */
    public function testSendTemplateKeepsExactNumericValues(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/sms.send_template.success.json');

        $client->sms->sendTemplate(new SendTemplateSmsRequest(
            templateId: 'otp_login',
            parameters: [
                'code' => '459122',
                'invoice' => '001234',
                'exact_amount' => '1.50',
                'quantity' => 2,
                'rate' => 19.99,
            ],
            receptor: '98912xxxxxxx',
            lineNumber: '3000xxxx',
        ));

        $parameters = json_decode((string) $http->only()->getBody(), true)['parameters'];
        self::assertSame('001234', $parameters['invoice']);
        self::assertSame('1.50', $parameters['exact_amount']);
        self::assertSame(2, $parameters['quantity']);
        self::assertSame(19.99, $parameters['rate']);
    }

    public function testGetStatusJoinsIdsIntoCsvQueryParams(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/sms.get_status.success.json');

        $client->sms->getStatus(new GetSmsStatusRequest(messageIds: ['m1', 'm2'], localIds: ['l1']));

        $request = $http->only();
        self::assertSame('GET', $request->getMethod());
        self::assertSame('/v1/sms/status', $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame('m1,m2', $query['message_ids']);
        self::assertSame('l1', $query['local_ids']);
    }

    public function testGetStatusOmitsAnEmptyIdList(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/sms.get_status.success.json');

        $client->sms->getStatus(new GetSmsStatusRequest(messageIds: ['m1']));

        parse_str($http->only()->getUri()->getQuery(), $query);
        self::assertSame(['message_ids' => 'm1'], $query);
    }

    public function testCancel(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/sms.cancel.success.json');

        $result = $client->sms->cancel(new CancelSmsRequest(messageIds: ['m1']));

        self::assertSame('/v1/sms/cancel', $http->only()->getUri()->getPath());
        self::assertNotEmpty($result->cancelledMessages + $result->failedToCancel);
    }

    public function testGetReceived(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/sms.get_received.success.json');

        $result = $client->sms->getReceived(new GetReceivedSmsRequest(lineNumber: '3000xxxx', count: 10));

        parse_str($http->only()->getUri()->getQuery(), $query);
        self::assertSame('3000xxxx', $query['line_number']);
        self::assertSame('10', $query['count']);
        self::assertNotEmpty($result->messages);
    }

    /**
     * @return iterable<string, array{callable(): mixed}>
     */
    public static function invalidRequestProvider(): iterable
    {
        yield 'empty receptor' => [static fn () => new SendSingleSmsRequest('', '3000', 'm')];
        yield 'empty line number' => [static fn () => new SendSingleSmsRequest('a', '', 'm')];
        yield 'empty message' => [static fn () => new SendSingleSmsRequest('a', '3000', '')];
        yield 'message over the limit' => [static fn () => new SendSingleSmsRequest('a', '3000', str_repeat('x', 901))];
        yield 'invalid local id' => [static fn () => new SendSingleSmsRequest('a', '3000', 'm', localId: '-bad')];
        yield 'no receptors' => [static fn () => new SendBulkSmsRequest([], 'm', '3000')];
        yield 'no messages' => [static fn () => new SendP2PSmsRequest([], '3000')];
        yield 'status with neither id list' => [static fn () => new GetSmsStatusRequest()];
        yield 'cancel with neither id list' => [static fn () => new CancelSmsRequest()];
        yield 'received count of zero' => [static fn () => new GetReceivedSmsRequest('3000', 0)];
        yield 'received count over the limit' => [static fn () => new GetReceivedSmsRequest('3000', 500)];
        yield 'received with empty line number' => [static fn () => new GetReceivedSmsRequest('')];
    }

    /**
     * PHP validates inside the request DTO's constructor, so an invalid request
     * cannot even be built — no HTTP client is involved.
     *
     * @param callable(): mixed $build
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidRequestProvider')]
    public function testInvalidRequestsAreRejectedAtConstruction(callable $build): void
    {
        $this->expectException(AdsefidValidationException::class);
        $build();
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function validReceiveCountProvider(): iterable
    {
        yield 'minimum' => [1];
        yield 'middle' => [250];
        yield 'maximum' => [499];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validReceiveCountProvider')]
    public function testReceiveCountBoundariesAreAccepted(int $count): void
    {
        $request = new GetReceivedSmsRequest('3000xxxx', $count);
        self::assertSame($count, $request->count);
    }
}
