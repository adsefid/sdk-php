<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Support\Wire;

final class SendP2PSmsResponse
{
    /**
     * @param list<P2PSmsMessageResult> $messages One entry per requested item, in request order; see P2PSmsMessageResult for the partial-success semantics.
     * @param array<int, int> $counts
     */
    public function __construct(
        public readonly string $groupId,
        public readonly array $messages,
        public readonly ?\DateTimeImmutable $sendTime,
        public readonly string $lineNumber,
        public readonly LineSelector|int|null $lineSelector,
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
            groupId: Wire::string($data, 'group_id'),
            messages: array_map(
                static fn (array $item): P2PSmsMessageResult => P2PSmsMessageResult::fromArray($item),
                array_values(Wire::array($data, 'messages')),
            ),
            sendTime: Wire::nullableDate($data, 'send_time'),
            lineNumber: Wire::string($data, 'line_number'),
            lineSelector: Wire::lineSelector($data, 'line_selector'),
            totalCost: Wire::float($data, 'total_cost'),
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
            'messages' => array_map(static fn (P2PSmsMessageResult $item): array => $item->toArray(), $this->messages),
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'line_number' => $this->lineNumber,
            'line_selector' => Wire::value($this->lineSelector),
            'total_cost' => $this->totalCost,
            'counts' => $counts,
        ];
    }
}
