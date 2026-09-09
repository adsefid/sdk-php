<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Support;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Enums\WebServiceResponseCode;

/**
 * Splits a raw `WebServiceCode` (doc §3.3) by range: `1000-1999` is a
 * `WebServiceMessageStatus`, `2000+` is a `WebServiceResponseCode`. A bulk or
 * P2P item carries either, depending on whether that one item was accepted or
 * rejected, and a code this SDK does not know yet maps to neither.
 */
final class WebServiceCode
{
    // Untyped: typed class constants require PHP 8.3; this SDK targets PHP 8.2+.
    public const MESSAGE_STATUS_MIN = 1000;
    public const ERROR_CODE_MIN = 2000;

    private function __construct()
    {
    }

    public static function isMessageStatus(int $code): bool
    {
        return $code >= self::MESSAGE_STATUS_MIN && $code < self::ERROR_CODE_MIN;
    }

    public static function isErrorCode(int $code): bool
    {
        return $code >= self::ERROR_CODE_MIN;
    }

    public static function messageStatus(int $code): ?WebServiceMessageStatus
    {
        return self::isMessageStatus($code) ? WebServiceMessageStatus::tryFrom($code) : null;
    }

    public static function errorCode(int $code): ?WebServiceResponseCode
    {
        return self::isErrorCode($code) ? WebServiceResponseCode::tryFrom($code) : null;
    }
}
