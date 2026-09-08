<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Enums\WebServiceResponseCode;

final class SendP2PSmsResponse
{
    /**
     * Per-item `status` is a `WebServiceCode` (doc 3.3): 1000-1999 means the
     * item was accepted (`messageStatus` set, `errorCode` null); 2000+ means
     * that specific receptor failed (`errorCode` set, `messageStatus` null).
     * `statusCode` always holds the raw value so unknown future codes never crash decoding.
     *
     * @param array<int, array{message_id: ?string, receptor: string, statusCode: int, messageStatus: ?WebServiceMessageStatus, errorCode: ?WebServiceResponseCode, local_id: ?string, message: string, hide: bool, segment_count: int, cost: float}> $messages
     * @param array<int, int> $counts
     */
    public function __construct(
        public readonly string $groupId,
        public readonly array $messages,
        public readonly ?\DateTimeImmutable $sendTime,
        public readonly string $lineNumber,
        public readonly ?LineSelector $lineSelector,
        public readonly float $totalCost,
        public readonly array $counts,
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
            messages: array_map(
                static function (array $message): array {
                    $statusCode = (int) $message['status'];

                    return [
                        'message_id' => isset($message['message_id']) ? (string) $message['message_id'] : null,
                        'receptor' => (string) $message['receptor'],
                        'statusCode' => $statusCode,
                        'messageStatus' => WebServiceMessageStatus::tryFrom($statusCode),
                        'errorCode' => WebServiceResponseCode::tryFrom($statusCode),
                        'local_id' => isset($message['local_id']) ? (string) $message['local_id'] : null,
                        'message' => (string) $message['message'],
                        'hide' => (bool) $message['hide'],
                        'segment_count' => (int) $message['segment_count'],
                        'cost' => (float) $message['cost'],
                    ];
                },
                (array) $data['messages'],
            ),
            sendTime: isset($data['send_time']) ? new \DateTimeImmutable((string) $data['send_time']) : null,
            lineNumber: (string) $data['line_number'],
            lineSelector: isset($data['line_selector']) ? LineSelector::from((int) $data['line_selector']) : null,
            totalCost: (float) $data['total_cost'],
            counts: $counts,
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
            'messages' => array_map(
                static fn (array $message): array => [
                    'message_id' => $message['message_id'],
                    'receptor' => $message['receptor'],
                    'status' => $message['statusCode'],
                    'local_id' => $message['local_id'],
                    'message' => $message['message'],
                    'hide' => $message['hide'],
                    'segment_count' => $message['segment_count'],
                    'cost' => $message['cost'],
                ],
                $this->messages,
            ),
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'line_number' => $this->lineNumber,
            'line_selector' => $this->lineSelector?->value,
            'total_cost' => $this->totalCost,
            'counts' => $counts,
        ];
    }
}
