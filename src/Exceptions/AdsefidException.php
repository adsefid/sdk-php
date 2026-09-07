<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Exceptions;

/**
 * Base class for every exception this SDK throws. Catch this to handle any
 * SDK failure generically, or catch one of its subclasses
 * (`AdsefidApiException`, `AdsefidRateLimitException`,
 * `AdsefidTransportException`, `AdsefidValidationException`,
 * `AdsefidWebhookVerificationException`) to handle a specific failure mode.
 */
abstract class AdsefidException extends \RuntimeException
{
}
