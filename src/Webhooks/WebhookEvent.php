<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Webhooks;

/**
 * Common envelope fields shared by every webhook event type. Returned by
 * `WebhookVerifier::verifyAndParse()` as one of its concrete subclasses
 * (`ReceiveWebhookEvent`, `StatusWebhookEvent`, `MessengerStatusWebhookEvent`).
 */
abstract readonly class WebhookEvent
{
    /**
     * @param string $type    One of the `WebhookEventTypes` constants.
     * @param int    $attempt 1-based delivery attempt number; the platform retries a delivery your endpoint didn't acknowledge with HTTP 2xx.
     * @param string $version The webhook payload schema version.
     */
    public function __construct(
        public string $id,
        public string $type,
        public \DateTimeImmutable $occurredAt,
        public int $attempt,
        public string $version,
    ) {
    }
}
