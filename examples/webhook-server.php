<?php

declare(strict_types=1);

// Run with: php -S localhost:8080 examples/webhook-server.php

require __DIR__ . '/../vendor/autoload.php';

use Adsefid\Sdk\Exceptions\AdsefidWebhookVerificationException;
use Adsefid\Sdk\Webhooks\MessengerStatusWebhookEvent;
use Adsefid\Sdk\Webhooks\ReceiveWebhookEvent;
use Adsefid\Sdk\Webhooks\StatusWebhookEvent;
use Adsefid\Sdk\Webhooks\WebhookHeaders;
use Adsefid\Sdk\Webhooks\WebhookVerifier;

$secret = getenv('ADSEFID_WEBHOOK_SECRET');

if ($secret === false || $secret === '') {
    http_response_code(500);
    echo "Missing ADSEFID_WEBHOOK_SECRET environment variable.\n";
    exit;
}

$rawBody = file_get_contents('php://input') ?: '';
$signatureHeader = $_SERVER[WebhookHeaders::SERVER_SIGNATURE] ?? '';
$timestampHeader = $_SERVER[WebhookHeaders::SERVER_TIMESTAMP] ?? '';

$verifier = new WebhookVerifier($secret);

try {
    $event = $verifier->verifyAndParse($rawBody, $signatureHeader, $timestampHeader);
} catch (AdsefidWebhookVerificationException $exception) {
    http_response_code(400);
    printf("Webhook verification failed: %s\n", $exception->getMessage());
    exit;
}

// Only event types this webhook endpoint is subscribed to (in your adsefid.com panel) ever
// arrive here — an endpoint subscribed to just "receive" never sees a StatusWebhookEvent.
$summary = match (true) {
    $event instanceof ReceiveWebhookEvent => sprintf(
        'Received %d inbound message(s) on line(s): %s',
        count($event->data),
        implode(', ', array_map(static fn (array $item): string => $item['line_number'], $event->data)),
    ),
    $event instanceof StatusWebhookEvent => sprintf(
        'Status update for %d message(s): %s',
        count($event->data),
        implode(', ', array_map(static fn (array $item): string => $item['status_delivery']->name, $event->data)),
    ),
    $event instanceof MessengerStatusWebhookEvent => sprintf(
        'Messenger status update for %d message(s): %s',
        count($event->data),
        implode(', ', array_map(static fn (array $item): string => $item['status_delivery']->name, $event->data)),
    ),
    default => sprintf('Unhandled event type "%s"', $event->type),
};

http_response_code(200);
printf("event id=%s type=%s attempt=%d occurred_at=%s\n", $event->id, $event->type, $event->attempt, $event->occurredAt->format(DATE_ATOM));
echo $summary . "\n";
