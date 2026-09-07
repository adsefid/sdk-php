<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Webhooks;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;

final readonly class StatusWebhookEvent extends WebhookEvent
{
    /**
     * @param array<int, array{id: string, local_id: ?string, status_delivery: WebServiceMessageStatus, delivery_time: ?\DateTimeImmutable}> $data
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
                    'id' => (string) $item['id'],
                    'local_id' => isset($item['local_id']) ? (string) $item['local_id'] : null,
                    'status_delivery' => WebServiceMessageStatus::from((int) $item['status_delivery']),
                    'delivery_time' => isset($item['delivery_time']) ? new \DateTimeImmutable((string) $item['delivery_time']) : null,
                ],
                (array) ($payload['data'] ?? []),
            ),
        );
    }
}
