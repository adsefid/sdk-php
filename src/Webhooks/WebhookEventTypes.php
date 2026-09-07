<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Webhooks;

/**
 * Values of a webhook payload's `type` field, used by `WebhookVerifier` to
 * pick which `WebhookEvent` subclass to parse the payload into.
 */
final class WebhookEventTypes
{
    public const RECEIVE = 'receive';
    public const STATUS = 'status';
    public const MESSENGER_STATUS = 'messenger.status';
}
