<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Enums\WebServiceResponseCode;

final class SendP2PMessengerResponse
{
    /**
     * Per-item `status` is a `WebServiceCode` (doc 3.3): 1000-1999 means the
     * item was accepted (`messageStatus` set, `errorCode` null); 2000+ means
     * that specific receptor failed (`errorCode` set, `messageStatus` null).
     * `statusCode` always holds the raw value so unknown future codes never crash decoding.
     *
     * @param array<int, array{message_id: ?string, receptor: string, message: string, local_id: ?string, hide: bool, statusCode: int, messageStatus: ?WebServiceMessageStatus, errorCode: ?WebServiceResponseCode, cost: int}> $receptors
     * @param array<int, int> $counts
     */
    public function __construct(
        public readonly string $groupId,
        public readonly array $receptors,
        public readonly ?\DateTimeImmutable $sendTime,
        public readonly int $totalCount,
        public readonly int $totalCost,
        public readonly array $counts,
        public readonly string $profile,
        public readonly string $messenger,
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
                        'message' => (string) $receptor['message'],
                        'local_id' => isset($receptor['local_id']) ? (string) $receptor['local_id'] : null,
                        'hide' => (bool) $receptor['hide'],
                        'statusCode' => $statusCode,
                        'messageStatus' => WebServiceMessageStatus::tryFrom($statusCode),
                        'errorCode' => WebServiceResponseCode::tryFrom($statusCode),
                        'cost' => (int) $receptor['cost'],
                    ];
                },
                (array) $data['receptors'],
            ),
            sendTime: isset($data['send_time']) ? new \DateTimeImmutable((string) $data['send_time']) : null,
            totalCount: (int) $data['total_count'],
            totalCost: (int) $data['total_cost'],
            counts: $counts,
            profile: (string) $data['profile'],
            messenger: (string) $data['messenger'],
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
                    'message' => $receptor['message'],
                    'local_id' => $receptor['local_id'],
                    'hide' => $receptor['hide'],
                    'status' => $receptor['statusCode'],
                    'cost' => $receptor['cost'],
                ],
                $this->receptors,
            ),
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'total_count' => $this->totalCount,
            'total_cost' => $this->totalCost,
            'counts' => $counts,
            'profile' => $this->profile,
            'messenger' => $this->messenger,
        ];
    }
}
