<?php

declare(strict_types=1);

namespace Adsefid\Sdk;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Composer\InstalledVersions;

final class ClientConfig
{
    // Untyped: typed class constants require PHP 8.3; this SDK targets PHP 8.2+.
    public const DEFAULT_BASE_URL = 'https://api.adsefid.com';

    public readonly string $userAgent;

    public function __construct(
        public readonly string $apiKey,
        public readonly string $baseUrl = self::DEFAULT_BASE_URL,
        ?string $userAgent = null,
    ) {
        // Fail here rather than letting a blank key surface later as a
        // confusing 401 from the service. The sibling SDKs reject it at
        // construction too.
        if (trim($this->apiKey) === '') {
            throw new AdsefidValidationException('apiKey is required and must be non-blank.', 'apiKey');
        }

        if (trim($this->baseUrl) === '') {
            throw new AdsefidValidationException('baseUrl is required and must be non-blank.', 'baseUrl');
        }

        $this->userAgent = $userAgent ?? self::defaultUserAgent();
        if (trim($this->userAgent) === '' || str_contains($this->userAgent, "\r") || str_contains($this->userAgent, "\n")) {
            throw new AdsefidValidationException(
                'userAgent must be non-blank and contain no line breaks.',
                'userAgent',
            );
        }
    }

    private static function defaultUserAgent(): string
    {
        try {
            $version = InstalledVersions::getPrettyVersion('adsefid/sdk');
        } catch (\OutOfBoundsException) {
            $version = null;
        }

        if ($version === null || str_contains($version, 'no-version-set')) {
            $version = '0+unknown';
        }

        return 'adsefid-php/' . ltrim($version, 'v');
    }
}
