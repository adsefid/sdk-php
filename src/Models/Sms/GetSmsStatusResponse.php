<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;

final class GetSmsStatusResponse
{
    /**
     * @param array<int, array{message_id: string, local_id: ?string, status: WebServiceMessageStatus, receptor: string, send_time: ?\DateTimeImmutable, delivery_time: ?\DateTimeImmutable}> $receptors
     */
    public function __construct(
        public readonly array $receptors,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            receptors: array_map(
                static fn (array $receptor): array => [
                    'message_id' => (string) $receptor['message_id'],
                    'local_id' => isset($receptor['local_id']) ? (string) $receptor['local_id'] : null,
                    'status' => WebServiceMessageStatus::from((int) $receptor['status']),
                    'receptor' => (string) $receptor['receptor'],
                    'send_time' => isset($receptor['send_time']) ? new \DateTimeImmutable((string) $receptor['send_time']) : null,
                    'delivery_time' => isset($receptor['delivery_time']) ? new \DateTimeImmutable((string) $receptor['delivery_time']) : null,
                ],
                (array) ($data['receptors'] ?? []),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'receptors' => array_map(
                static fn (array $receptor): array => [
                    'message_id' => $receptor['message_id'],
                    'local_id' => $receptor['local_id'],
                    'status' => $receptor['status']->value,
                    'receptor' => $receptor['receptor'],
                    'send_time' => $receptor['send_time']?->format(DATE_ATOM),
                    'delivery_time' => $receptor['delivery_time']?->format(DATE_ATOM),
                ],
                $this->receptors,
            ),
        ];
    }
}
