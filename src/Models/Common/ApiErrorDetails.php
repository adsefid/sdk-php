<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Common;

/** Structured `error.details`, shared by all API endpoint families. */
final class ApiErrorDetails
{
    /**
     * @param ?array<string, ApiFieldError> $errors
     * @param ?list<ApiItemError>           $items
     */
    public function __construct(
        public readonly ?array $errors = null,
        public readonly ?array $items = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $rawItems = $data['items'] ?? null;
        $items = null;
        if (is_array($rawItems)) {
            $items = [];
            foreach ($rawItems as $item) {
                if (is_array($item)) {
                    $items[] = ApiItemError::fromArray($item);
                }
            }
        }

        return new self(
            errors: self::parseErrors($data['errors'] ?? null),
            items: $items,
        );
    }

    /**
     * @return ?array<string, ApiFieldError>
     */
    private static function parseErrors(mixed $data): ?array
    {
        if (!is_array($data)) {
            return null;
        }

        $errors = [];
        foreach ($data as $field => $error) {
            if (is_string($field) && is_array($error)) {
                $errors[$field] = ApiFieldError::fromArray($error);
            }
        }

        return $errors;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [];
        if ($this->errors !== null) {
            $data['errors'] = array_map(
                static fn (ApiFieldError $error): array => $error->toArray(),
                $this->errors,
            );
        }
        if ($this->items !== null) {
            $data['items'] = array_map(
                static fn (ApiItemError $item): array => $item->toArray(),
                $this->items,
            );
        }

        return $data;
    }
}
