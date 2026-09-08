<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Webhooks;

use Adsefid\Sdk\Exceptions\AdsefidWebhookVerificationException;

/**
 * Verifies the HMAC signature and freshness of an incoming adsefid.com
 * webhook delivery, then parses the body into a typed `WebhookEvent`.
 */
final class WebhookVerifier
{
    private const SIGNATURE_PREFIX = 'v1=';

    /** The raw HMAC key: the decoded form of the panel's Base64 secret. */
    private readonly string $key;

    /**
     * @param string $secret The webhook signing secret exactly as shown in your adsefid.com panel.
     *                       That is the Base64 encoding of 32 random bytes, and the platform signs
     *                       with the decoded bytes, so it is decoded here before it is used as an
     *                       HMAC key. To supply an already-decoded key, use `fromKey()`.
     *
     * @throws AdsefidWebhookVerificationException if $secret is not valid Base64.
     */
    public function __construct(string $secret)
    {
        $decoded = base64_decode(trim($secret), true);
        if ($decoded === false || $decoded === '') {
            throw new AdsefidWebhookVerificationException(
                'Webhook secret is not valid Base64; use the secret exactly as shown in your adsefid.com panel, or construct with fromKey().',
            );
        }

        $this->key = $decoded;
    }

    /**
     * Builds a verifier from an already-decoded signing key, skipping the
     * Base64 step. Use this when you store the decoded key yourself.
     */
    public static function fromKey(string $key): self
    {
        // Round-tripping through the constructor keeps it the single place the
        // key is ever assigned, which readonly requires anyway.
        return new self(base64_encode($key));
    }

    /**
     * @param string $rawBody          The exact, unmodified request body bytes (signing depends on this being byte-for-byte identical to what the server sent).
     * @param string $signatureHeader  Value of the `X-Atlas-Webhook-Signature` header (see `WebhookHeaders::SIGNATURE`/`SERVER_SIGNATURE`).
     * @param string $timestampHeader  Value of the `X-Atlas-Webhook-Timestamp` header (see `WebhookHeaders::TIMESTAMP`/`SERVER_TIMESTAMP`).
     * @param int    $maxAgeSeconds    Maximum allowed difference between now and the delivery timestamp, in either direction.
     *
     * @throws AdsefidWebhookVerificationException if the timestamp is not a valid unix timestamp, is stale/too far in the future, the signature is missing/invalid, or the body is not valid/recognized JSON.
     */
    public function verifyAndParse(
        string $rawBody,
        string $signatureHeader,
        string $timestampHeader,
        int $maxAgeSeconds = 300,
    ): WebhookEvent {
        $timestamp = $this->parseTimestamp($timestampHeader);
        // Signature first, then freshness — the sibling SDKs check in this
        // order, so the same request reports the same failure everywhere.
        $this->assertValidSignature($rawBody, $signatureHeader, $timestamp);
        $this->assertFresh($timestamp, $maxAgeSeconds);

        return $this->parsePayload($rawBody);
    }

    private function parseTimestamp(string $timestampHeader): int
    {
        if (!ctype_digit($timestampHeader)) {
            throw new AdsefidWebhookVerificationException('Webhook timestamp header is not a valid unix timestamp.');
        }

        return (int) $timestampHeader;
    }

    private function assertFresh(int $timestamp, int $maxAgeSeconds): void
    {
        if (abs(time() - $timestamp) > $maxAgeSeconds) {
            throw new AdsefidWebhookVerificationException('Webhook timestamp is stale or too far in the future.');
        }
    }

    private function assertValidSignature(string $rawBody, string $signatureHeader, int $timestamp): void
    {
        if (!str_starts_with($signatureHeader, self::SIGNATURE_PREFIX)) {
            throw new AdsefidWebhookVerificationException('Webhook signature header is missing the "v1=" prefix.');
        }

        $providedSignature = substr($signatureHeader, strlen(self::SIGNATURE_PREFIX));
        $signingInput = sprintf('%d.%s', $timestamp, $rawBody);
        $expectedSignature = base64_encode(hash_hmac('sha256', $signingInput, $this->key, true));

        if (!hash_equals($expectedSignature, $providedSignature)) {
            throw new AdsefidWebhookVerificationException('Webhook signature verification failed.');
        }
    }

    private function parsePayload(string $rawBody): WebhookEvent
    {
        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new AdsefidWebhookVerificationException('Webhook body is not valid JSON.');
        }

        if (!is_array($payload)) {
            throw new AdsefidWebhookVerificationException('Webhook body must be a JSON object.');
        }

        $this->assertPayloadShape($payload);

        try {
            return match ($payload['type']) {
                WebhookEventTypes::RECEIVE => ReceiveWebhookEvent::fromArray($payload),
                WebhookEventTypes::STATUS => StatusWebhookEvent::fromArray($payload),
                WebhookEventTypes::MESSENGER_STATUS => MessengerStatusWebhookEvent::fromArray($payload),
                default => throw new AdsefidWebhookVerificationException(
                    sprintf('Unknown webhook event type "%s".', $payload['type']),
                ),
            };
        } catch (AdsefidWebhookVerificationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new AdsefidWebhookVerificationException('Webhook body has an invalid payload shape.', 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertPayloadShape(array $payload): void
    {
        if (!isset($payload['id'], $payload['type'], $payload['occurred_at'], $payload['attempt'], $payload['version'], $payload['data'])
            || !is_string($payload['id'])
            || !is_string($payload['type'])
            || !is_string($payload['occurred_at'])
            || !is_int($payload['attempt'])
            || !is_string($payload['version'])
            || !is_array($payload['data'])) {
            throw new AdsefidWebhookVerificationException('Webhook body is missing required fields or contains invalid field types.');
        }

        foreach ($payload['data'] as $item) {
            if (!is_array($item)) {
                throw new AdsefidWebhookVerificationException('Webhook data entries must be JSON objects.');
            }

            if ($payload['type'] === WebhookEventTypes::RECEIVE) {
                if (!isset($item['id'], $item['line_number'], $item['sender'], $item['message'], $item['receive_date'])
                    || !is_int($item['id'])
                    || !is_string($item['line_number'])
                    || !is_string($item['sender'])
                    || !is_string($item['message'])
                    || !is_string($item['receive_date'])) {
                    throw new AdsefidWebhookVerificationException('Receive webhook data contains invalid fields.');
                }
            } elseif ($payload['type'] === WebhookEventTypes::STATUS || $payload['type'] === WebhookEventTypes::MESSENGER_STATUS) {
                if (!isset($item['id'], $item['status_delivery'])
                    || !is_string($item['id'])
                    || !is_int($item['status_delivery'])
                    || (array_key_exists('local_id', $item) && $item['local_id'] !== null && !is_string($item['local_id']))
                    || (array_key_exists('delivery_time', $item) && $item['delivery_time'] !== null && !is_string($item['delivery_time']))) {
                    throw new AdsefidWebhookVerificationException('Status webhook data contains invalid fields.');
                }
            }
        }
    }
}
