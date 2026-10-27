<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Support\WebServiceCode;
use Adsefid\Sdk\Support\Wire;

/**
 * `statusCode` is the raw status; `status` is its typed view and is `null`
 * for a code this SDK does not know yet, so a new server-side status never
 * fails the call.
 */
final class SendSingleMessengerResponse
{
    public function __construct(
        public readonly string $groupId,
        public readonly string $messageId,
        public readonly int $statusCode,
        public readonly ?WebServiceMessageStatus $status,
        public readonly string $receptor,
        public readonly ?string $localId,
        public readonly bool $hide,
        public readonly float $cost,
        public readonly ?\DateTimeImmutable $sendTime,
        public readonly string $profile,
        public readonly string $messenger,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            groupId: Wire::string($data, 'group_id'),
            messageId: Wire::string($data, 'message_id'),
            statusCode: Wire::int($data, 'status'),
            status: WebServiceCode::messageStatus(Wire::int($data, 'status')),
            receptor: Wire::string($data, 'receptor'),
            localId: isset($data['local_id']) ? Wire::string($data, 'local_id') : null,
            hide: Wire::bool($data, 'hide'),
            cost: Wire::float($data, 'cost'),
            sendTime: Wire::nullableDate($data, 'send_time'),
            profile: Wire::string($data, 'profile'),
            messenger: Wire::string($data, 'messenger'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'group_id' => $this->groupId,
            'message_id' => $this->messageId,
            'status' => $this->statusCode,
            'receptor' => $this->receptor,
            'local_id' => $this->localId,
            'hide' => $this->hide,
            'cost' => $this->cost,
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'profile' => $this->profile,
            'messenger' => $this->messenger,
        ];
    }
}
