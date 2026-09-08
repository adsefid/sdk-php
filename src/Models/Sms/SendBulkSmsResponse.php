<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Enums\WebServiceResponseCode;

final class SendBulkSmsResponse
{
    /**
     * Per-item `status` is a `WebServiceCode` (doc 3.3): 1000-1999 means the
     * item was accepted (`messageStatus` set, `errorCode` null); 2000+ means
     * that specific receptor failed (`errorCode` set, `messageStatus` null).
     * `statusCode` always holds the raw value so unknown future codes never crash decoding.
     *
     * @param array<int, array{message_id: ?string, receptor: string, local_id: ?string, statusCode: int, messageStatus: ?WebServiceMessageStatus, errorCode: ?WebServiceResponseCode, hide: bool, cost: float}> $receptors
     * @param array<int, int> $counts
     */
    public function __construct(
        public readonly string $groupId,
        public readonly array $receptors,
        public readonly string $message,
        public readonly int $segmentCount,
        public readonly ?\DateTimeImmutable $sendTime,
        public readonly string $lineNumber,
        public readonly ?LineSelector $lineSelector,
        public readonly array $counts,
        public readonly int $totalCount,
        public readonly float $totalCost,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $counts = [];

        foreach ((array) ($data['counts'] ?? []) as $code => $count) {
            $counts[(int) $code] = (int) $count;
        }

        return new self(
            groupId: (string) $data['group_id'],
            receptors: array_map(
                static function (array $receptor): array {
                    $statusCode = (int) $receptor['status'];

                    return [
                        'message_id' => isset($receptor['message_id']) ? (string) $receptor['message_id'] : null,
                        'receptor' => (string) $receptor['receptor'],
                        'local_id' => isset($receptor['local_id']) ? (string) $receptor['local_id'] : null,
                        'statusCode' => $statusCode,
                        'messageStatus' => WebServiceMessageStatus::tryFrom($statusCode),
                        'errorCode' => WebServiceResponseCode::tryFrom($statusCode),
                        'hide' => (bool) $receptor['hide'],
                        'cost' => (float) $receptor['cost'],
                    ];
                },
                (array) $data['receptors'],
            ),
            message: (string) $data['message'],
            segmentCount: (int) $data['segment_count'],
            sendTime: isset($data['send_time']) ? new \DateTimeImmutable((string) $data['send_time']) : null,
            lineNumber: (string) $data['line_number'],
            lineSelector: isset($data['line_selector']) ? LineSelector::from((int) $data['line_selector']) : null,
            counts: $counts,
            totalCount: (int) $data['total_count'],
            totalCost: (float) $data['total_cost'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $counts = [];

        foreach ($this->counts as $code => $count) {
            $counts[(string) $code] = $count;
        }

        return [
            'group_id' => $this->groupId,
            'receptors' => array_map(
                static fn (array $receptor): array => [
                    'message_id' => $receptor['message_id'],
                    'receptor' => $receptor['receptor'],
                    'local_id' => $receptor['local_id'],
                    'status' => $receptor['statusCode'],
                    'hide' => $receptor['hide'],
                    'cost' => $receptor['cost'],
                ],
                $this->receptors,
            ),
            'message' => $this->message,
            'segment_count' => $this->segmentCount,
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'line_number' => $this->lineNumber,
            'line_selector' => $this->lineSelector?->value,
            'counts' => $counts,
            'total_count' => $this->totalCount,
            'total_cost' => $this->totalCost,
        ];
    }
}
