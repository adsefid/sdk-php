<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Webhooks;

final readonly class StatusWebhookEvent extends WebhookEvent
{
    /**
     * @param list<StatusUpdateItem> $data
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
                static fn (array $item): StatusUpdateItem => StatusUpdateItem::fromArray($item),
                array_values((array) ($payload['data'] ?? [])),
            ),
        );
    }
}
