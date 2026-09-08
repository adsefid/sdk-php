<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests\Support;

/**
 * Reproduces the platform's webhook signing, for tests needing a fresh
 * signature rather than the fixed golden vector.
 */
final class WebhookSigner
{
    public const PREFIX = 'v1=';

    private function __construct()
    {
    }

    /**
     * HMAC-SHA256 over "{timestamp}.{rawBody}", keyed with the DECODED secret.
     * The panel shows the secret Base64-encoded; the key is what it decodes to.
     */
    public static function sign(string $secretBase64, string $timestamp, string $rawBody): string
    {
        $key = base64_decode($secretBase64, true);
        if ($key === false) {
            throw new \InvalidArgumentException('Test secret is not valid Base64.');
        }

        return self::PREFIX . base64_encode(hash_hmac('sha256', $timestamp . '.' . $rawBody, $key, true));
    }

    public static function now(int $offsetSeconds = 0): string
    {
        return (string) (time() + $offsetSeconds);
    }
}
