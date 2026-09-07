<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\User;

final class GetUserTemplatesResponse
{
    /**
     * @param UserTemplateItem[] $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            items: array_map(
                static fn (array $item): UserTemplateItem => UserTemplateItem::fromArray($item),
                (array) ($data['items'] ?? []),
            ),
            total: (int) ($data['total'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(static fn (UserTemplateItem $item): array => $item->toArray(), $this->items),
            'total' => $this->total,
        ];
    }
}
