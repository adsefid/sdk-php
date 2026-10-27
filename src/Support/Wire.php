<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Support;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Enums\TemplateState;
use Adsefid\Sdk\Exceptions\AdsefidTransportException;

/**
 * Typed reads from a decoded response body. A missing key or a value of the wrong type is a
 * malformed response, reported as {@see AdsefidTransportException} instead of a PHP warning or
 * a silently coerced default.
 *
 * @internal
 */
final class Wire
{
    private function __construct()
    {
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function string(array $data, string $key): string
    {
        $value = self::required($data, $key);

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw self::malformed($key, 'a string');
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function int(array $data, string $key): int
    {
        $value = self::required($data, $key);

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw self::malformed($key, 'an integer');
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function float(array $data, string $key): float
    {
        $value = self::required($data, $key);

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return (float) $value;
        }

        throw self::malformed($key, 'a number');
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function bool(array $data, string $key): bool
    {
        $value = self::required($data, $key);

        if (is_bool($value)) {
            return $value;
        }

        throw self::malformed($key, 'a boolean');
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function date(array $data, string $key): \DateTimeImmutable
    {
        $value = self::required($data, $key);

        return self::parseDate($value, $key);
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function nullableDate(array $data, string $key): ?\DateTimeImmutable
    {
        $value = $data[$key] ?? null;

        return $value === null ? null : self::parseDate($value, $key);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    public static function array(array $data, string $key): array
    {
        $value = self::required($data, $key);

        if (!is_array($value)) {
            throw self::malformed($key, 'an array');
        }

        return $value;
    }

    /**
     * A known line selector, or the raw number for one this SDK does not know yet.
     *
     * @param array<array-key, mixed> $data
     */
    public static function lineSelector(array $data, string $key): LineSelector|int|null
    {
        if (($data[$key] ?? null) === null) {
            return null;
        }

        return self::requiredLineSelector($data, $key);
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function requiredLineSelector(array $data, string $key): LineSelector|int
    {
        $raw = self::int($data, $key);

        return LineSelector::tryFrom($raw) ?? $raw;
    }

    /**
     * A known template state, or the raw string for one this SDK does not know yet.
     *
     * @param array<array-key, mixed> $data
     */
    public static function templateState(array $data, string $key): TemplateState|string
    {
        $raw = self::string($data, $key);

        return TemplateState::tryFrom($raw) ?? $raw;
    }

    /**
     * The wire value of an enum member or of the raw value that stood in for an unknown one.
     */
    public static function value(\BackedEnum|int|string|null $value): int|string|null
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private static function required(array $data, string $key): mixed
    {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            throw new AdsefidTransportException(sprintf('The API response is missing "%s".', $key));
        }

        return $data[$key];
    }

    private static function parseDate(mixed $value, string $key): \DateTimeImmutable
    {
        if (!is_string($value)) {
            throw self::malformed($key, 'an ISO-8601 datetime string');
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $exception) {
            throw new AdsefidTransportException(sprintf('The API response has an invalid datetime in "%s".', $key), $exception);
        }
    }

    private static function malformed(string $key, string $expected): AdsefidTransportException
    {
        return new AdsefidTransportException(sprintf('The API response field "%s" must be %s.', $key, $expected));
    }
}
