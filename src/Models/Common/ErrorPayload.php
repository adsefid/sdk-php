<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Common;

/**
 * Maps the `error` object of the standard error envelope:
 * `{"status":"error","error":{"code":..,"name":..,"details":..}}`.
 *
 * `details` is intentionally `mixed` (decoded JSON: array|null) — its shape
 * is endpoint-specific (validation map, bulk item list, cancel-specific
 * map, or absent) and is never modeled as a strong type.
 */
final class ErrorPayload
{
    public function __construct(
        public readonly int $code,
        public readonly string $name,
        public readonly mixed $details,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: (int) ($data['code'] ?? 0),
            name: (string) ($data['name'] ?? 'UNKNOWN_ERROR'),
            details: $data['details'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'details' => $this->details,
        ];
    }
}
