<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\User;

use Adsefid\Sdk\Enums\LineSelector;

final class UserLine
{
    public function __construct(
        public readonly string $lineNumber,
        public readonly LineSelector $lineSelector,
        public readonly string $lineName,
        public readonly bool $enabled,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            lineNumber: (string) $data['line_number'],
            lineSelector: LineSelector::from((int) $data['line_selector']),
            lineName: (string) $data['line_name'],
            enabled: (bool) $data['enabled'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'line_number' => $this->lineNumber,
            'line_selector' => $this->lineSelector->value,
            'line_name' => $this->lineName,
            'enabled' => $this->enabled,
        ];
    }
}
