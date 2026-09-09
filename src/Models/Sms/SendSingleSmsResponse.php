<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Support\WebServiceCode;

/**
 * `statusCode` is the raw status; `status` is its typed view and is `null`
 * for a code this SDK does not know yet, so a new server-side status never
 * fails the call.
 */
final class SendSingleSmsResponse
{
    public function __construct(
        public readonly string $groupId,
        public readonly ?string $localId,
        public readonly int $statusCode,
        public readonly ?WebServiceMessageStatus $status,
        public readonly string $lineNumber,
        public readonly ?LineSelector $lineSelector,
        public readonly float $cost,
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
            statusCode: (int) $data['status'],
            status: WebServiceCode::messageStatus((int) $data['status']),
            lineNumber: (string) $data['line_number'],
            lineSelector: isset($data['line_selector']) ? LineSelector::from((int) $data['line_selector']) : null,
            cost: (float) $data['cost'],
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
            'status' => $this->statusCode,
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
