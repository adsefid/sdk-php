<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Support\Wire;

final class SendBulkSmsResponse
{
    /**
     * @param list<BulkSmsReceptorResult> $receptors One entry per requested item, in request order; see BulkSmsReceptorResult for the partial-success semantics.
     * @param array<int, int> $counts
     */
    public function __construct(
        public readonly string $groupId,
        public readonly array $receptors,
        public readonly string $message,
        public readonly int $segmentCount,
        public readonly ?\DateTimeImmutable $sendTime,
        public readonly string $lineNumber,
        public readonly LineSelector|int|null $lineSelector,
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
            groupId: Wire::string($data, 'group_id'),
            receptors: array_map(
                static fn (array $item): BulkSmsReceptorResult => BulkSmsReceptorResult::fromArray($item),
                array_values(Wire::array($data, 'receptors')),
            ),
            message: Wire::string($data, 'message'),
            segmentCount: Wire::int($data, 'segment_count'),
            sendTime: Wire::nullableDate($data, 'send_time'),
            lineNumber: Wire::string($data, 'line_number'),
            lineSelector: Wire::lineSelector($data, 'line_selector'),
            counts: $counts,
            totalCount: Wire::int($data, 'total_count'),
            totalCost: Wire::float($data, 'total_cost'),
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
            'receptors' => array_map(static fn (BulkSmsReceptorResult $item): array => $item->toArray(), $this->receptors),
            'message' => $this->message,
            'segment_count' => $this->segmentCount,
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'line_number' => $this->lineNumber,
            'line_selector' => Wire::value($this->lineSelector),
            'counts' => $counts,
            'total_count' => $this->totalCount,
            'total_cost' => $this->totalCost,
        ];
    }
}
