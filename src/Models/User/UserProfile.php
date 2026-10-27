<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\User;

use Adsefid\Sdk\Support\Wire;

final class UserProfile
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $messenger,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Wire::string($data, 'id'),
            name: Wire::string($data, 'name'),
            messenger: Wire::string($data, 'messenger'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'messenger' => $this->messenger,
        ];
    }
}
