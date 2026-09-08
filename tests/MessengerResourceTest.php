<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Http\MultipartStreamBuilder;
use Adsefid\Sdk\Models\Messenger\CancelMessengerRequest;
use Adsefid\Sdk\Models\Messenger\GetMessengerStatusRequest;
use Adsefid\Sdk\Models\Messenger\SendBulkMessengerRequest;
use Adsefid\Sdk\Models\Messenger\SendSingleMessengerRequest;
use Adsefid\Sdk\Models\Messenger\SendTemplateMessengerRequest;
use Adsefid\Sdk\Models\Messenger\UploadMessengerFileRequest;
use Adsefid\Sdk\Tests\Support\TestClient;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MessengerResourceTest extends TestCase
{
    public function testSendSingleBuildsTheRequest(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/messenger.send_single.success.json');

        $client->messenger->sendSingle(new SendSingleMessengerRequest(
            message: 'hi',
            receptor: '98912xxxxxxx',
            profile: 'profile-1',
        ));

        $request = $http->only();
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/v1/messenger/single', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true);
        foreach (['file_id', 'send_time', 'local_id'] as $absent) {
            self::assertArrayNotHasKey($absent, $body);
        }
    }

    public function testBulkPartialSuccessIsNotAnError(): void
    {
        [$client] = TestClient::respondingWithFixture('envelopes/messenger.send_bulk.partial_success.json');

        $result = $client->messenger->sendBulk(new SendBulkMessengerRequest(
            receptors: [['receptor' => 'a'], ['receptor' => 'b']],
            message: 'm',
            profile: 'p',
        ));

        self::assertSame([1000, 2025], array_column($result->receptors, 'statusCode'));
        self::assertNull($result->receptors[1]['message_id']);
    }

    public function testSendTemplateKeepsLeadingZeros(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/messenger.send_template.success.json');

        $client->messenger->sendTemplate(new SendTemplateMessengerRequest(
            templateId: 'invoice_notice',
            parameters: ['invoice' => '001234', 'amount' => 2],
            receptor: '98912xxxxxxx',
            profile: 'p',
        ));

        $parameters = json_decode((string) $http->only()->getBody(), true)['parameters'];
        self::assertSame('001234', $parameters['invoice']);
        self::assertSame(2, $parameters['amount']);
    }

    public function testGetStatusBuildsTheQuery(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/messenger.get_status.success.json');

        $client->messenger->getStatus(new GetMessengerStatusRequest(messageIds: ['m1'], localIds: ['l1']));

        parse_str($http->only()->getUri()->getQuery(), $query);
        self::assertSame('m1', $query['message_ids']);
        self::assertSame('l1', $query['local_ids']);
    }

    public function testUploadFilePostsMultipart(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/messenger.upload_file.success.json');

        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, 'file-contents-here');
        rewind($stream);

        $result = $client->messenger->uploadFile(new UploadMessengerFileRequest(
            stream: $stream,
            filename: 'invoice.pdf',
            contentType: 'application/pdf',
        ));

        $request = $http->only();
        self::assertSame('/v1/messenger/file', $request->getUri()->getPath());
        self::assertStringStartsWith('multipart/form-data; boundary=', $request->getHeaderLine('Content-Type'));

        $body = (string) $request->getBody();
        self::assertStringContainsString('name="file"', $body);
        self::assertStringContainsString('filename="invoice.pdf"', $body);
        self::assertStringContainsString('Content-Type: application/pdf', $body);
        self::assertStringContainsString('file-contents-here', $body);

        self::assertNotSame('', $result->fileId);
    }

    public function testTheMultipartBoundaryIsRandomPerBuilder(): void
    {
        $factory = new HttpFactory();

        // Two builders must not share a boundary, or concurrent uploads could
        // collide. This is why tests parse the boundary instead of hard-coding.
        self::assertNotSame(
            (new MultipartStreamBuilder($factory))->getBoundary(),
            (new MultipartStreamBuilder($factory))->getBoundary(),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function headerInjectionProvider(): iterable
    {
        yield 'carriage return in the filename' => ["evil\r.txt"];
        yield 'newline in the filename' => ["evil\n.txt"];
        yield 'null byte in the filename' => ["evil\0.txt"];
    }

    /**
     * A filename is interpolated into a multipart header, so a control
     * character in it would let a caller inject their own headers.
     */
    #[DataProvider('headerInjectionProvider')]
    public function testControlCharactersInAFilenameAreRejected(string $filename): void
    {
        $factory = new HttpFactory();
        $builder = new MultipartStreamBuilder($factory);
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);

        $this->expectException(AdsefidValidationException::class);
        $builder->buildSingleFile('file', $filename, 'text/plain', $stream);
    }

    public function testAQuoteInAFilenameIsEscapedNotRejected(): void
    {
        $factory = new HttpFactory();
        $builder = new MultipartStreamBuilder($factory);
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, 'x');
        rewind($stream);

        $body = (string) $builder->buildSingleFile('file', 'we"ird.txt', 'text/plain', $stream);

        self::assertStringContainsString('filename="we\\"ird.txt"', $body);
    }

    /**
     * @return iterable<string, array{callable(): mixed}>
     */
    public static function invalidRequestProvider(): iterable
    {
        yield 'empty message' => [static fn () => new SendSingleMessengerRequest('', 'a', 'p')];
        yield 'empty profile' => [static fn () => new SendSingleMessengerRequest('m', 'a', '')];
        yield 'message over the limit' => [static fn () => new SendSingleMessengerRequest(str_repeat('x', 4001), 'a', 'p')];
        yield 'no receptors' => [static fn () => new SendBulkMessengerRequest([], 'm', 'p')];
        yield 'cancel with neither id list' => [static fn () => new CancelMessengerRequest()];
        yield 'status with neither id list' => [static fn () => new GetMessengerStatusRequest()];
    }

    /**
     * @param callable(): mixed $build
     */
    #[DataProvider('invalidRequestProvider')]
    public function testInvalidRequestsAreRejectedAtConstruction(callable $build): void
    {
        $this->expectException(AdsefidValidationException::class);
        $build();
    }
}
