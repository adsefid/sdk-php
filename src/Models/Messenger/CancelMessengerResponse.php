<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;

final class CancelMessengerResponse
{
    /**
     * @param array<int, array{message_id: string, local_id: ?string, status: WebServiceMessageStatus}> $cancelledMessages
     * @param array<int, array{message_id: string, local_id: ?string, status: WebServiceMessageStatus}> $failedToCancel
     */
    public function __construct(
        public readonly array $cancelledMessages,
        public readonly array $failedToCancel,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $mapItem = static fn (array $item): array => [
            'message_id' => (string) $item['message_id'],
            'local_id' => isset($item['local_id']) ? (string) $item['local_id'] : null,
            'status' => WebServiceMessageStatus::from((int) $item['status']),
        ];

        return new self(
            cancelledMessages: array_map($mapItem, (array) ($data['cancelled_messages'] ?? [])),
            failedToCancel: array_map($mapItem, (array) ($data['failed_to_cancel'] ?? [])),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $mapItem = static fn (array $item): array => [
            'message_id' => $item['message_id'],
            'local_id' => $item['local_id'],
            'status' => $item['status']->value,
        ];

        return [
            'cancelled_messages' => array_map($mapItem, $this->cancelledMessages),
            'failed_to_cancel' => array_map($mapItem, $this->failedToCancel),
        ];
    }
}
