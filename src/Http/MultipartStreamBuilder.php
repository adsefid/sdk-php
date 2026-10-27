<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Http;

use Adsefid\Sdk\Exceptions\AdsefidTransportException;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 */
final class MultipartStreamBuilder
{
    /** Largest file content the service accepts: 15 MiB, inclusive. Multipart framing is not counted. */
    public const MAX_FILE_BYTES = 15 * 1024 * 1024;

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

        $content = stream_get_contents($resource, self::MAX_FILE_BYTES + 1);

        if ($content === false) {
            throw new AdsefidTransportException('Failed to read the file stream for multipart upload.');
        }

        if (strlen($content) > self::MAX_FILE_BYTES) {
            throw new AdsefidValidationException(sprintf('The file content must be at most %d bytes (15 MiB).', self::MAX_FILE_BYTES), 'stream');
        }

        return $this->streamFactory->createStream(
            $parts[0] . $content . sprintf("\r\n--%s--\r\n", $this->boundary),
        );
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
