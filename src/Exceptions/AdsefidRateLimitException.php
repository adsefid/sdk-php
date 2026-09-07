<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Exceptions;

/**
 * Thrown instead of `AdsefidApiException` when the API reports
 * `MESSAGE_LIMIT_REACHED` (2035) or `REQUEST_LIMIT_REACHED` (2036), or
 * returns a bare HTTP 429 with an unparseable body. Carries the same
 * properties as `AdsefidApiException`.
 */
class AdsefidRateLimitException extends AdsefidApiException
{
}
