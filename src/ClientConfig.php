<?php

declare(strict_types=1);

namespace Adsefid\Sdk;

final class ClientConfig
{
    // Untyped: typed class constants require PHP 8.3; this SDK targets PHP 8.2+.
    public const DEFAULT_BASE_URL = 'https://api.adsefid.com';

    public function __construct(
        public readonly string $apiKey,
        public readonly string $baseUrl = self::DEFAULT_BASE_URL,
    ) {
    }
}
