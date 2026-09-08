<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests;

use Adsefid\Sdk\ClientConfig;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Models\Messenger\GetMessengerStatusRequest;
use Adsefid\Sdk\Models\Sms\GetSmsStatusRequest;
use Adsefid\Sdk\Tests\Support\TestClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClientConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new ClientConfig(apiKey: 'k');

        self::assertSame('https://api.adsefid.com', $config->baseUrl);
        self::assertStringStartsWith('adsefid-php/', $config->userAgent);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidUserAgentProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'blank' => ['   '];
        yield 'newline' => ["bad\nagent"];
        yield 'carriage return' => ["bad\ragent"];
    }

    #[DataProvider('invalidUserAgentProvider')]
    public function testAnUnusableUserAgentIsRejected(string $userAgent): void
    {
        $this->expectException(AdsefidValidationException::class);
        new ClientConfig(apiKey: 'k', userAgent: $userAgent);
    }

    public function testABlankApiKeyIsRejected(): void
    {
        $this->expectException(AdsefidValidationException::class);
        new ClientConfig(apiKey: '');
    }

    public function testTheClientRoutesThroughEachResource(): void
    {
        [$client, $http] = TestClient::build([
            TestClient::json(200, '{"status":"success","data":{"receptors":[]}}'),
            TestClient::json(200, '{"status":"success","data":{"receptors":[]}}'),
            TestClient::json(200, '{"status":"success","data":[]}'),
        ]);

        $client->sms->getStatus(new GetSmsStatusRequest(messageIds: ['m1']));
        $client->messenger->getStatus(new GetMessengerStatusRequest(messageIds: ['m1']));
        $client->user->getLines();

        self::assertSame(
            ['/v1/sms/status', '/v1/messenger/status', '/v1/user/lines'],
            array_map(
                static fn ($request) => $request->getUri()->getPath(),
                $http->requests(),
            ),
        );
    }

    public function testEveryRequestCarriesTheApiKeyAndUserAgent(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/user.get_info.success.json');

        $client->user->getInfo();

        $request = $http->only();
        self::assertSame(TestClient::API_KEY, $request->getHeaderLine('X-API-KEY'));
        // Never assert an exact version: it comes from installed package
        // metadata and reads as 0+unknown inside this repository.
        self::assertStringStartsWith('adsefid-php/', $request->getHeaderLine('User-Agent'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
    }
}
