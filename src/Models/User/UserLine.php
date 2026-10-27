<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\User;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Support\Wire;

final class UserLine
{
    public function __construct(
        public readonly string $lineNumber,
        public readonly LineSelector|int $lineSelector,
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
            lineNumber: Wire::string($data, 'line_number'),
            lineSelector: Wire::requiredLineSelector($data, 'line_selector'),
            lineName: Wire::string($data, 'line_name'),
            enabled: Wire::bool($data, 'enabled'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'line_number' => $this->lineNumber,
            'line_selector' => Wire::value($this->lineSelector),
            'line_name' => $this->lineName,
            'enabled' => $this->enabled,
        ];
    }
}
