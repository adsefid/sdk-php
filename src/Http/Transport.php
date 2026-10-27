<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Http;

use Adsefid\Sdk\Enums\WebServiceResponseCode;
use Adsefid\Sdk\Exceptions\AdsefidApiException;
use Adsefid\Sdk\Exceptions\AdsefidException;
use Adsefid\Sdk\Exceptions\AdsefidRateLimitException;
use Adsefid\Sdk\Exceptions\AdsefidTransportException;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Models\Common\ErrorPayload;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 */
final class Transport
{
    private const HTTP_TOO_MANY_REQUESTS = 429;

    private const UNKNOWN_ERROR_CODE = -1;

    /** Longest path-and-query sent; the service's proxy rejects request lines over 8 KiB with an HTML 414. */
    public const MAX_REQUEST_TARGET_LENGTH = 8000;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $userAgent,
    ) {
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * Builds a response model from a decoded body. Anything other than an SDK exception means the
     * body had an unexpected shape, which is reported as a transport failure rather than leaking a
     * `TypeError` or `ValueError`.
     *
     * @template T of object
     *
     * @param callable(array<array-key, mixed>): T $factory
     * @param array<array-key, mixed>              $data
     *
     * @return T
     */
    public function map(callable $factory, array $data): object
    {
        try {
            return $factory($data);
        } catch (AdsefidException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new AdsefidTransportException('The API response had an unexpected shape.', $exception);
        }
    }

    /**
     * Builds response models from a decoded list body; see {@see map()}.
     *
     * @template T of object
     *
     * @param callable(array<array-key, mixed>): T $factory
     * @param array<array-key, mixed>              $data
     *
     * @return list<T>
     */
    public function mapList(callable $factory, array $data): array
    {
        if (!array_is_list($data)) {
            throw new AdsefidTransportException('The API returned a JSON object where an array payload was expected.');
        }

        $items = [];

        foreach ($data as $item) {
            if (!is_array($item)) {
                throw new AdsefidTransportException('The API returned a non-object entry in an array payload.');
            }

            $items[] = $this->map($factory, $item);
        }

        return $items;
    }

    /**
     * @param array<string, string|list<string>|null> $query
     *
     * @return array<array-key, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        $request = $this->requestFactory
            ->createRequest('GET', $this->buildUrl($path, $query, true))
            ->withHeader('X-API-KEY', $this->apiKey)
            ->withHeader('User-Agent', $this->userAgent)
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
        $json = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $request = $this->requestFactory
            ->createRequest('POST', $this->buildUrl($path))
            ->withHeader('X-API-KEY', $this->apiKey)
            ->withHeader('User-Agent', $this->userAgent)
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
            ->withHeader('User-Agent', $this->userAgent)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Accept', 'application/json')
            ->withBody($body);

        return $this->send($request);
    }

    /**
     * A string is escaped as one value; a list is escaped per item, deduplicated, and joined with a
     * literal comma, which the service reads as the separator and which keeps the URL a third
     * shorter than `%2C` would.
     *
     * @param array<string, string|list<string>|null> $query
     */
    private function buildUrl(string $path, array $query = [], bool $enforceLength = false): string
    {
        $basePath = (string) parse_url($this->baseUrl, PHP_URL_PATH);
        $target = rtrim($basePath, '/') . '/' . ltrim($path, '/');

        $pairs = [];
        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }

            $encoded = is_string($value)
                ? rawurlencode($value)
                : implode(',', array_map('rawurlencode', array_values(array_unique($value))));

            if ($encoded !== '') {
                $pairs[] = $key . '=' . $encoded;
            }
        }

        if ($pairs !== []) {
            $target .= '?' . implode('&', $pairs);
        }

        if ($enforceLength && strlen($target) > self::MAX_REQUEST_TARGET_LENGTH) {
            throw new AdsefidValidationException(sprintf(
                'The request URL is %d characters long, over the %d the service accepts; send fewer IDs per call.',
                strlen($target),
                self::MAX_REQUEST_TARGET_LENGTH,
            ), 'request');
        }

        return rtrim($this->baseUrl, '/') . substr($target, strlen(rtrim($basePath, '/')));
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

        $isSuccess = $httpStatusCode >= 200 && $httpStatusCode < 300;
        $isErrorEnvelope = is_array($decoded) && ($decoded['status'] ?? null) === 'error' && is_array($decoded['error'] ?? null);

        if ($isSuccess && is_array($decoded) && ($decoded['status'] ?? null) === 'success') {
            if (!is_array($decoded['data'] ?? null)) {
                throw new AdsefidTransportException('adsefid.com API returned a success envelope without a usable "data" payload.');
            }

            return $decoded['data'];
        }

        if ($isErrorEnvelope) {
            /** @var array<string, mixed> $errorData */
            $errorData = $decoded['error'];
            $error = ErrorPayload::fromArray($errorData);
            $code = WebServiceResponseCode::tryFrom($error->code);

            if ($error->code === WebServiceResponseCode::MessageLimitReached->value
                || $error->code === WebServiceResponseCode::RequestLimitReached->value
                || $httpStatusCode === self::HTTP_TOO_MANY_REQUESTS) {
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

        if ($httpStatusCode === self::HTTP_TOO_MANY_REQUESTS) {
            throw new AdsefidRateLimitException(
                'adsefid.com API returned HTTP 429 without a recognizable error envelope.',
                WebServiceResponseCode::RequestLimitReached->value,
                WebServiceResponseCode::RequestLimitReached,
                'RATE_LIMITED',
                $httpStatusCode,
                null,
            );
        }

        if (!$isSuccess) {
            throw new AdsefidApiException(
                sprintf('adsefid.com API returned HTTP %d without a recognizable error envelope.', $httpStatusCode),
                self::UNKNOWN_ERROR_CODE,
                null,
                'UNKNOWN_ERROR',
                $httpStatusCode,
                null,
            );
        }

        throw new AdsefidTransportException('adsefid.com API returned a malformed success envelope.');
    }
}
