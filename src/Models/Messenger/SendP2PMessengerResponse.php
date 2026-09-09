<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

final class SendP2PMessengerResponse
{
    /**
     * @param list<P2PMessengerReceptorResult> $receptors One entry per requested item, in request order; see P2PMessengerReceptorResult for the partial-success semantics.
     * @param array<int, int> $counts
     */
    public function __construct(
        public readonly string $groupId,
        public readonly array $receptors,
        public readonly ?\DateTimeImmutable $sendTime,
        public readonly int $totalCount,
        public readonly float $totalCost,
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
                static fn (array $item): P2PMessengerReceptorResult => P2PMessengerReceptorResult::fromArray($item),
                array_values((array) $data['receptors']),
            ),
            sendTime: isset($data['send_time']) ? new \DateTimeImmutable((string) $data['send_time']) : null,
            totalCount: (int) $data['total_count'],
            totalCost: (float) $data['total_cost'],
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
            'receptors' => array_map(static fn (P2PMessengerReceptorResult $item): array => $item->toArray(), $this->receptors),
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'total_count' => $this->totalCount,
            'total_cost' => $this->totalCost,
            'counts' => $counts,
            'profile' => $this->profile,
            'messenger' => $this->messenger,
        ];
    }
}
