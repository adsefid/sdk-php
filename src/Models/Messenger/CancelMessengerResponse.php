<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Models\Common\CancelledMessage;

final class CancelMessengerResponse
{
    /**
     * @param list<CancelledMessage> $cancelledMessages
     * @param list<CancelledMessage> $failedToCancel Messages that could not be cancelled (e.g. already sent), with their current status.
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
        $mapItem = static fn (array $item): CancelledMessage => CancelledMessage::fromArray($item);

        return new self(
            cancelledMessages: array_map($mapItem, array_values((array) ($data['cancelled_messages'] ?? []))),
            failedToCancel: array_map($mapItem, array_values((array) ($data['failed_to_cancel'] ?? []))),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $mapItem = static fn (CancelledMessage $item): array => $item->toArray();

        return [
            'cancelled_messages' => array_map($mapItem, $this->cancelledMessages),
            'failed_to_cancel' => array_map($mapItem, $this->failedToCancel),
        ];
    }
}
