<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Common;

/**
 * Maps the `error` object of the standard error envelope:
 * `{"status":"error","error":{"code":..,"name":..,"details":..}}`.
 */
final class ErrorPayload
{
    public function __construct(
        public readonly int $code,
        public readonly string $name,
        public readonly ?ApiErrorDetails $details,
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
            details: is_array($data['details'] ?? null)
                ? ApiErrorDetails::fromArray($data['details'])
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'code' => $this->code,
            'name' => $this->name,
        ];

        if ($this->details !== null) {
            $data['details'] = $this->details->toArray();
        }

        return $data;
    }
}
