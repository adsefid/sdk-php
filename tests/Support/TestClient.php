<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests\Support;

use Adsefid\Sdk\AdsefidClient;
use Adsefid\Sdk\ClientConfig;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

final class TestClient
{
    public const API_KEY = 'test-api-key';
    public const BASE_URL = 'https://api.test';

    private function __construct()
    {
    }

    /**
     * @param list<ResponseInterface|ClientExceptionInterface> $queue
     *
     * @return array{0: AdsefidClient, 1: RecordingHttpClient}
     */
    public static function build(array $queue): array
    {
        $httpClient = new RecordingHttpClient($queue);
        $factory = new HttpFactory();

        $client = new AdsefidClient(
            new ClientConfig(apiKey: self::API_KEY, baseUrl: self::BASE_URL),
            $httpClient,
            $factory,
            $factory,
        );

        return [$client, $httpClient];
    }

    /**
     * @return array{0: AdsefidClient, 1: RecordingHttpClient}
     */
    public static function respondingWith(string $body, int $status = 200): array
    {
        return self::build([self::json($status, $body)]);
    }

    /**
     * @return array{0: AdsefidClient, 1: RecordingHttpClient}
     */
    public static function respondingWithFixture(string $fixture, int $status = 200): array
    {
        return self::respondingWith(Fixtures::bytes($fixture), $status);
    }

    public static function json(int $status, string $body): ResponseInterface
    {
        return new Response($status, ['Content-Type' => 'application/json'], $body);
    }
}
