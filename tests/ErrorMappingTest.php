<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests;

use Adsefid\Sdk\Enums\WebServiceResponseCode;
use Adsefid\Sdk\Exceptions\AdsefidApiException;
use Adsefid\Sdk\Exceptions\AdsefidException;
use Adsefid\Sdk\Exceptions\AdsefidRateLimitException;
use Adsefid\Sdk\Exceptions\AdsefidTransportException;
use Adsefid\Sdk\Tests\Support\Fixtures;
use Adsefid\Sdk\Tests\Support\TestClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ErrorMappingTest extends TestCase
{
    /**
     * @return iterable<string, array{string, int, bool, int, string}>
     */
    public static function errorEnvelopeProvider(): iterable
    {
        yield 'unauthorized' => ['errors/error.invalid_api_key.json', 401, false, 2018, 'UNAUTHORIZED'];
        yield 'message rate limit' => ['errors/error.rate_limit_message.json', 429, true, 2035, 'MESSAGE_LIMIT_REACHED'];
        // The envelope wins over the HTTP status: a 200 carrying an error
        // envelope is still an error.
        yield 'request rate limit inside a 200' => ['errors/error.rate_limit_request.json', 200, true, 2036, 'REQUEST_LIMIT_REACHED'];
        yield 'invalid parameter' => ['errors/error.invalid_parameter.json', 400, false, 2024, 'INVALID_PARAMETER'];
        yield 'an unmapped future code' => ['errors/error.unknown_code.json', 400, false, 2999, 'SOME_FUTURE_SERVER_ERROR'];
    }

    #[DataProvider('errorEnvelopeProvider')]
    public function testErrorEnvelopesMapToTypedExceptions(
        string $fixture,
        int $httpStatus,
        bool $rateLimited,
        int $code,
        string $name,
    ): void {
        [$client] = TestClient::respondingWithFixture($fixture, $httpStatus);

        try {
            $client->user->getInfo();
            self::fail('expected an API exception');
        } catch (AdsefidApiException $exception) {
            self::assertSame($code, $exception->rawCode);
            self::assertSame($name, $exception->name);
            self::assertSame($httpStatus, $exception->httpStatusCode);
            self::assertSame($rateLimited, $exception instanceof AdsefidRateLimitException);
        }
    }

    public function testAnUnmappedCodeLeavesTheTypedEnumNull(): void
    {
        [$client] = TestClient::respondingWithFixture('errors/error.unknown_code.json', 400);

        try {
            $client->user->getInfo();
            self::fail('expected an API exception');
        } catch (AdsefidApiException $exception) {
            // The raw int always survives so a newer service code never breaks
            // an older SDK; the typed view is simply null.
            self::assertSame(2999, $exception->rawCode);
            self::assertNull($exception->responseCode);
        }
    }

    public function testAMappedCodeExposesTheEnum(): void
    {
        [$client] = TestClient::respondingWithFixture('errors/error.invalid_parameter.json', 400);

        try {
            $client->user->getInfo();
            self::fail('expected an API exception');
        } catch (AdsefidApiException $exception) {
            self::assertSame(WebServiceResponseCode::InvalidParameter, $exception->responseCode);
        }
    }

    public function testDetailsSurviveIntact(): void
    {
        [$client] = TestClient::respondingWithFixture('errors/error.invalid_parameter.json', 400);

        try {
            $client->user->getInfo();
            self::fail('expected an API exception');
        } catch (AdsefidApiException $exception) {
            self::assertSame(
                [
                    'take' => ['must be between 1 and 100'],
                    'state' => ['must be one of pendingapproval, approved, rejected'],
                ],
                $exception->details,
            );
        }
    }

    public function testA500WithAnHtmlBodyIsAnApiException(): void
    {
        [$client] = TestClient::respondingWith(Fixtures::bytes('errors/error.not_json.txt'), 500);

        $this->expectException(AdsefidApiException::class);
        $client->user->getInfo();
    }

    public function testABare429IsStillARateLimit(): void
    {
        [$client] = TestClient::respondingWith(Fixtures::bytes('errors/error.not_json.txt'), 429);

        $this->expectException(AdsefidRateLimitException::class);
        $client->user->getInfo();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedSuccessProvider(): iterable
    {
        yield 'empty body' => [''];
        yield 'not json' => ['<html>nope</html>'];
        yield 'unrecognized status word' => ['{"status":"partial","data":{}}'];
    }

    /**
     * A 2xx whose body is not a usable success envelope is a transport-level
     * problem, not an API error: the service never said anything meaningful.
     */
    #[DataProvider('malformedSuccessProvider')]
    public function testAMalformedSuccessEnvelopeIsATransportException(string $body): void
    {
        [$client] = TestClient::respondingWith($body, 200);

        $this->expectException(AdsefidTransportException::class);
        $client->user->getInfo();
    }

    public function testANetworkFailureBecomesATransportException(): void
    {
        [$client] = TestClient::build([
            new ConnectException('connection refused', new Request('GET', '/v1/user/info')),
        ]);

        $this->expectException(AdsefidTransportException::class);
        $client->user->getInfo();
    }

    /**
     * A rate limit is an API exception, so a broad `catch (AdsefidApiException)`
     * swallows it. Callers who want to treat rate limits specially must order
     * their catch blocks narrowest first — this pins that it really happens.
     */
    public function testARateLimitIsCaughtByABroadApiExceptionHandler(): void
    {
        [$client] = TestClient::respondingWithFixture('errors/error.rate_limit_message.json', 429);

        try {
            $client->user->getInfo();
            self::fail('expected an exception');
        } catch (AdsefidApiException $exception) {
            self::assertInstanceOf(AdsefidRateLimitException::class, $exception);
            self::assertSame(2035, $exception->rawCode);
        }
    }

    /**
     * Every exception this SDK raises shares one base class, so a caller can
     * wrap an entire integration in a single catch.
     */
    public function testEveryFailureSharesTheBaseException(): void
    {
        [$apiClient] = TestClient::respondingWithFixture('errors/error.invalid_api_key.json', 401);
        [$transportClient] = TestClient::build([
            new ConnectException('connection refused', new Request('GET', '/v1/user/info')),
        ]);

        foreach ([$apiClient, $transportClient] as $client) {
            try {
                $client->user->getInfo();
                self::fail('expected an exception');
            } catch (AdsefidException $exception) {
                self::assertInstanceOf(AdsefidException::class, $exception);
            }
        }
    }
}
