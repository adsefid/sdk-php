<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Common;

use Adsefid\Sdk\Enums\WebServiceResponseCode;

/** One field-level API error in an error envelope. */
final class ApiFieldError
{
    public function __construct(
        public readonly int $rawCode,
        public readonly ?WebServiceResponseCode $responseCode,
        public readonly string $name,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $rawCode = (int) ($data['code'] ?? 0);

        return new self(
            rawCode: $rawCode,
            responseCode: WebServiceResponseCode::tryFrom($rawCode),
            name: (string) ($data['name'] ?? 'UNKNOWN_ERROR'),
        );
    }

    /** @return array{code: int, name: string} */
    public function toArray(): array
    {
        return ['code' => $this->rawCode, 'name' => $this->name];
    }
}
