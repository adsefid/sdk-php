<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Support\Wire;

final class SendBulkMessengerResponse
{
    /**
     * @param list<BulkMessengerReceptorResult> $receptors One entry per requested item, in request order; see BulkMessengerReceptorResult for the partial-success semantics.
     * @param array<int, int> $counts
     */
    public function __construct(
        public readonly string $groupId,
        public readonly array $receptors,
        public readonly string $message,
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
            groupId: Wire::string($data, 'group_id'),
            receptors: array_map(
                static fn (array $item): BulkMessengerReceptorResult => BulkMessengerReceptorResult::fromArray($item),
                array_values(Wire::array($data, 'receptors')),
            ),
            message: Wire::string($data, 'message'),
            sendTime: Wire::nullableDate($data, 'send_time'),
            totalCount: Wire::int($data, 'total_count'),
            totalCost: Wire::float($data, 'total_cost'),
            counts: $counts,
            profile: Wire::string($data, 'profile'),
            messenger: Wire::string($data, 'messenger'),
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
            'receptors' => array_map(static fn (BulkMessengerReceptorResult $item): array => $item->toArray(), $this->receptors),
            'message' => $this->message,
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'total_count' => $this->totalCount,
            'total_cost' => $this->totalCost,
            'counts' => $counts,
            'profile' => $this->profile,
            'messenger' => $this->messenger,
        ];
    }
}
