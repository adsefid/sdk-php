<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Enums\WebServiceMessageStatus;

final class SendSingleSmsResponse
{
    public function __construct(
        public readonly string $groupId,
        public readonly ?string $localId,
        public readonly WebServiceMessageStatus $status,
        public readonly string $lineNumber,
        public readonly ?LineSelector $lineSelector,
        public readonly int $cost,
        public readonly string $receptor,
        public readonly ?\DateTimeImmutable $sendTime,
        public readonly string $messageId,
        public readonly int $segmentCount,
        public readonly bool $hide,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            groupId: (string) $data['group_id'],
            localId: isset($data['local_id']) ? (string) $data['local_id'] : null,
            status: WebServiceMessageStatus::from((int) $data['status']),
            lineNumber: (string) $data['line_number'],
            lineSelector: isset($data['line_selector']) ? LineSelector::from((int) $data['line_selector']) : null,
            cost: (int) $data['cost'],
            receptor: (string) $data['receptor'],
            sendTime: isset($data['send_time']) ? new \DateTimeImmutable((string) $data['send_time']) : null,
            messageId: (string) $data['message_id'],
            segmentCount: (int) $data['segment_count'],
            hide: (bool) $data['hide'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'group_id' => $this->groupId,
            'local_id' => $this->localId,
            'status' => $this->status->value,
            'line_number' => $this->lineNumber,
            'line_selector' => $this->lineSelector?->value,
            'cost' => $this->cost,
            'receptor' => $this->receptor,
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'message_id' => $this->messageId,
            'segment_count' => $this->segmentCount,
            'hide' => $this->hide,
        ];
    }
}
