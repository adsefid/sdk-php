<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Common;

/** Errors for one rejected item in a bulk or P2P request. */
final class ApiItemError
{
    /** @param array<string, ApiFieldError> $errors */
    public function __construct(
        public readonly int $index,
        public readonly array $errors,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $rawErrors = $data['errors'] ?? null;
        $errors = [];
        if (is_array($rawErrors)) {
            foreach ($rawErrors as $field => $error) {
                if (is_string($field) && is_array($error)) {
                    $errors[$field] = ApiFieldError::fromArray($error);
                }
            }
        }

        return new self(
            index: (int) ($data['index'] ?? 0),
            errors: $errors,
        );
    }

    /** @return array{index: int, errors: array<string, array{code: int, name: string}>} */
    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'errors' => array_map(
                static fn (ApiFieldError $error): array => $error->toArray(),
                $this->errors,
            ),
        ];
    }
}
