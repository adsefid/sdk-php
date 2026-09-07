<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Exceptions;

/**
 * Thrown by `WebhookVerifier::verifyAndParse()` when a webhook delivery
 * fails verification: a missing/invalid `v1=` signature, a stale or
 * future timestamp, or a malformed/unrecognized JSON payload.
 */
class AdsefidWebhookVerificationException extends AdsefidException
{
}
