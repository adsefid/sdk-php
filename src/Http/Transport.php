<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Http;

use Adsefid\Sdk\Enums\WebServiceResponseCode;
use Adsefid\Sdk\Exceptions\AdsefidApiException;
use Adsefid\Sdk\Exceptions\AdsefidRateLimitException;
use Adsefid\Sdk\Exceptions\AdsefidTransportException;
use Adsefid\Sdk\Models\Common\ErrorPayload;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

final class Transport
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * @param array<string, string|null> $query
     *
     * @return array<array-key, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        $request = $this->requestFactory
            ->createRequest('GET', $this->buildUrl($path, $query))
            ->withHeader('X-API-KEY', $this->apiKey)
            ->withHeader('Accept', 'application/json');

        return $this->send($request);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<array-key, mixed>
     */
    public function post(string $path, array $body): array
    {
        $json = json_encode($body, JSON_THROW_ON_ERROR);

        $request = $this->requestFactory
            ->createRequest('POST', $this->buildUrl($path))
            ->withHeader('X-API-KEY', $this->apiKey)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->streamFactory->createStream($json));

        return $this->send($request);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function postMultipart(string $path, StreamInterface $body, string $contentType): array
    {
        $request = $this->requestFactory
            ->createRequest('POST', $this->buildUrl($path))
            ->withHeader('X-API-KEY', $this->apiKey)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Accept', 'application/json')
            ->withBody($body);

        return $this->send($request);
    }

    /**
     * @param array<string, string|null> $query
     */
    private function buildUrl(string $path, array $query = []): string
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        $filtered = array_filter($query, static fn (?string $value): bool => $value !== null);

        if ($filtered !== []) {
            $url .= '?' . http_build_query($filtered, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function send(RequestInterface $request): array
    {
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new AdsefidTransportException('HTTP transport failure while calling adsefid.com API.', $exception);
        }

        return $this->decodeEnvelope($response);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodeEnvelope(ResponseInterface $response): array
    {
        $httpStatusCode = $response->getStatusCode();
        $rawBody = (string) $response->getBody();

        $decoded = null;

        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $decoded = null;
        }

        if ($httpStatusCode >= 200 && $httpStatusCode < 300 && is_array($decoded) && ($decoded['status'] ?? null) === 'success') {
            return is_array($decoded['data'] ?? null) ? $decoded['data'] : ['data' => $decoded['data'] ?? null];
        }

        if (is_array($decoded) && ($decoded['status'] ?? null) === 'error' && is_array($decoded['error'] ?? null)) {
            $error = ErrorPayload::fromArray($decoded['error']);
            $code = WebServiceResponseCode::tryFrom($error->code);

            if ($error->code === WebServiceResponseCode::MessageLimitReached->value
                || $error->code === WebServiceResponseCode::RequestLimitReached->value) {
                throw new AdsefidRateLimitException(
                    sprintf('adsefid.com API rate limit error: %s (%d)', $error->name, $error->code),
                    $error->code,
                    $code,
                    $error->name,
                    $httpStatusCode,
                    $error->details,
                );
            }

            throw new AdsefidApiException(
                sprintf('adsefid.com API error: %s (%d)', $error->name, $error->code),
                $error->code,
                $code,
                $error->name,
                $httpStatusCode,
                $error->details,
            );
        }

        if ($httpStatusCode === 429) {
            throw new AdsefidRateLimitException(
                'adsefid.com API returned HTTP 429 with an unparseable response body.',
                $httpStatusCode,
                null,
                'HTTP_429',
                $httpStatusCode,
                $rawBody !== '' ? $rawBody : null,
            );
        }

        if ($httpStatusCode < 200 || $httpStatusCode >= 300) {
            throw new AdsefidApiException(
                sprintf('adsefid.com API returned an unexpected HTTP %d response.', $httpStatusCode),
                $httpStatusCode,
                null,
                'HTTP_ERROR',
                $httpStatusCode,
                $rawBody !== '' ? $rawBody : null,
            );
        }

        throw new AdsefidTransportException('adsefid.com API returned a malformed success envelope.');
    }
}
