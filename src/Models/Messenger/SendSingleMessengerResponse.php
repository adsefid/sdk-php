<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;

final class SendSingleMessengerResponse
{
    public function __construct(
        public readonly string $groupId,
        public readonly string $messageId,
        public readonly WebServiceMessageStatus $status,
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
            groupId: (string) $data['group_id'],
            messageId: (string) $data['message_id'],
            status: WebServiceMessageStatus::from((int) $data['status']),
            receptor: (string) $data['receptor'],
            localId: isset($data['local_id']) ? (string) $data['local_id'] : null,
            hide: (bool) $data['hide'],
            cost: (float) $data['cost'],
            sendTime: isset($data['send_time']) ? new \DateTimeImmutable((string) $data['send_time']) : null,
            profile: (string) $data['profile'],
            messenger: (string) $data['messenger'],
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
            'status' => $this->status->value,
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
