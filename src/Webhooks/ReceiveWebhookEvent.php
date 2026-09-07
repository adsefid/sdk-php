<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Webhooks;

final readonly class ReceiveWebhookEvent extends WebhookEvent
{
    /**
     * @param array<int, array{id: int, line_number: string, sender: string, message: string, receive_date: \DateTimeImmutable}> $data
     */
    public function __construct(
        string $id,
        string $type,
        \DateTimeImmutable $occurredAt,
        int $attempt,
        string $version,
        public array $data,
    ) {
        parent::__construct($id, $type, $occurredAt, $attempt, $version);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: (string) $payload['id'],
            type: (string) $payload['type'],
            occurredAt: new \DateTimeImmutable((string) $payload['occurred_at']),
            attempt: (int) $payload['attempt'],
            version: (string) $payload['version'],
            data: array_map(
                static fn (array $item): array => [
                    'id' => (int) $item['id'],
                    'line_number' => (string) $item['line_number'],
                    'sender' => (string) $item['sender'],
                    'message' => (string) $item['message'],
                    'receive_date' => new \DateTimeImmutable((string) $item['receive_date']),
                ],
                (array) ($payload['data'] ?? []),
            ),
        );
    }
}
