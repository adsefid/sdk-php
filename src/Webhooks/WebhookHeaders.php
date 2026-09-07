<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Webhooks;

/**
 * Header names used by adsefid.com outgoing webhook deliveries, plus their mangled
 * `$_SERVER` superglobal equivalents (PHP's built-in web SAPI rewrites incoming header
 * names to `HTTP_` + uppercase + dashes-to-underscores) so you never have to work that
 * transformation out or type the header name yourself.
 */
final class WebhookHeaders
{
    public const ID = 'X-Atlas-Webhook-Id';
    public const SIGNATURE = 'X-Atlas-Webhook-Signature';
    public const TIMESTAMP = 'X-Atlas-Webhook-Timestamp';
    public const EVENT = 'X-Atlas-Webhook-Event';
    public const ATTEMPT = 'X-Atlas-Webhook-Attempt';

    public const SERVER_ID = 'HTTP_X_ATLAS_WEBHOOK_ID';
    public const SERVER_SIGNATURE = 'HTTP_X_ATLAS_WEBHOOK_SIGNATURE';
    public const SERVER_TIMESTAMP = 'HTTP_X_ATLAS_WEBHOOK_TIMESTAMP';
    public const SERVER_EVENT = 'HTTP_X_ATLAS_WEBHOOK_EVENT';
    public const SERVER_ATTEMPT = 'HTTP_X_ATLAS_WEBHOOK_ATTEMPT';
}
