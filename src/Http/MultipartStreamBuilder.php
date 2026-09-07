<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Http;

use Adsefid\Sdk\Exceptions\AdsefidTransportException;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

final class MultipartStreamBuilder
{
    private readonly string $boundary;

    public function __construct(
        private readonly StreamFactoryInterface $streamFactory,
    ) {
        $this->boundary = '----AdsefidSdkBoundary' . bin2hex(random_bytes(16));
    }

    public function getBoundary(): string
    {
        return $this->boundary;
    }

    public function getContentType(): string
    {
        return sprintf('multipart/form-data; boundary=%s', $this->boundary);
    }

    /**
     * Builds a single-file multipart/form-data body.
     *
     * @param resource $resource A readable PHP stream resource (e.g. from `fopen()`).
     */
    public function buildSingleFile(
        string $fieldName,
        string $filename,
        string $contentType,
        $resource,
    ): StreamInterface {
        $fieldName = self::escapeQuotedHeaderValue($fieldName, 'field name');
        $filename = self::escapeQuotedHeaderValue($filename, 'filename');
        self::assertSafeHeaderValue($contentType, 'content type');

        $parts = [];

        $parts[] = sprintf(
            "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\nContent-Type: %s\r\n\r\n",
            $this->boundary,
            $fieldName,
            $filename,
            $contentType,
        );

        $content = stream_get_contents($resource);

        if ($content === false) {
            throw new AdsefidTransportException('Failed to read the file stream for multipart upload.');
        }

        $parts[] = $content;
        $parts[] = sprintf("\r\n--%s--\r\n", $this->boundary);

        return $this->streamFactory->createStream(implode('', $parts));
    }

    private static function escapeQuotedHeaderValue(string $value, string $name): string
    {
        self::assertSafeHeaderValue($value, $name);

        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    private static function assertSafeHeaderValue(string $value, string $name): void
    {
        if (str_contains($value, "\r") || str_contains($value, "\n") || str_contains($value, "\0")) {
            throw new AdsefidValidationException(sprintf('Multipart %s contains forbidden control characters.', $name), $name);
        }
    }
}
