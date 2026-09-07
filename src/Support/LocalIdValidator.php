<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Support;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;

final class LocalIdValidator
{
    // Note: kept untyped (not `private const string ...`) because typed class
    // constants require PHP 8.3, and this SDK targets PHP 8.2+.
    private const LOCAL_ID_PATTERN = '/^[A-Za-z0-9]([A-Za-z0-9\-_.:]{0,34}[A-Za-z0-9])?$/';

    private function __construct()
    {
    }

    public static function validate(?string $localId, string $field = 'local_id'): void
    {
        if ($localId === null) {
            return;
        }

        if (preg_match(self::LOCAL_ID_PATTERN, $localId) !== 1) {
            throw new AdsefidValidationException(
                sprintf('"%s" must be 1-36 ASCII letters/digits with only "-", "_", ".", ":" allowed inside.', $field),
                $field,
            );
        }
    }

    public static function requireNonEmpty(?string $value, string $field): string
    {
        if ($value === null || $value === '') {
            throw new AdsefidValidationException(sprintf('"%s" is required.', $field), $field);
        }

        return $value;
    }

    public static function maxLength(string $value, int $max, string $field): void
    {
        if (mb_strlen($value) > $max) {
            throw new AdsefidValidationException(
                sprintf('"%s" must not exceed %d characters.', $field, $max),
                $field,
            );
        }
    }

    /**
     * @param array<array-key, mixed> $value
     */
    public static function requireNonEmptyArray(array $value, string $field): void
    {
        if ($value === []) {
            throw new AdsefidValidationException(sprintf('"%s" must contain at least 1 item.', $field), $field);
        }
    }

    public static function maxCount(int $count, int $max, string $field): void
    {
        if ($count > $max) {
            throw new AdsefidValidationException(
                sprintf('"%s" must not contain more than %d items.', $field, $max),
                $field,
            );
        }
    }

    public static function intRange(int $value, int $min, int $max, string $field): void
    {
        if ($value < $min || $value > $max) {
            throw new AdsefidValidationException(
                sprintf('"%s" must be between %d and %d.', $field, $min, $max),
                $field,
            );
        }
    }

    /**
     * @param array<int, array<array-key, mixed>|null> $values
     * @param array<int, string>                        $fields
     */
    public static function requireAtLeastOne(array $values, array $fields): void
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== []) {
                return;
            }
        }

        throw new AdsefidValidationException(
            sprintf('At least one of "%s" is required.', implode('", "', $fields)),
            implode(',', $fields),
        );
    }
}
