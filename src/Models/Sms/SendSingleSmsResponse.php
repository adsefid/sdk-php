<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Support\WebServiceCode;
use Adsefid\Sdk\Support\Wire;

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
        public readonly LineSelector|int|null $lineSelector,
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
            groupId: Wire::string($data, 'group_id'),
            localId: isset($data['local_id']) ? Wire::string($data, 'local_id') : null,
            statusCode: Wire::int($data, 'status'),
            status: WebServiceCode::messageStatus(Wire::int($data, 'status')),
            lineNumber: Wire::string($data, 'line_number'),
            lineSelector: Wire::lineSelector($data, 'line_selector'),
            cost: Wire::float($data, 'cost'),
            receptor: Wire::string($data, 'receptor'),
            sendTime: Wire::nullableDate($data, 'send_time'),
            messageId: Wire::string($data, 'message_id'),
            segmentCount: Wire::int($data, 'segment_count'),
            hide: Wire::bool($data, 'hide'),
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
            'line_selector' => Wire::value($this->lineSelector),
            'cost' => $this->cost,
            'receptor' => $this->receptor,
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'message_id' => $this->messageId,
            'segment_count' => $this->segmentCount,
            'hide' => $this->hide,
        ];
    }
}
