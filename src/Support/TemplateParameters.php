<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Support;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;

/**
 * Validation for the `parameters` map sent to the template endpoints.
 *
 * A template parameter value is either a JSON string or a JSON number. For a
 * parameter the template declares as `number`, the platform substitutes a
 * numeric *string* verbatim, which is the only way to keep a value's exact
 * digits: `'001234'` keeps its leading zeros and `'1.50'` its trailing zero,
 * where the numbers `1234` and `1.5` would not.
 *
 * @phpstan-type TemplateParameterValue string|int|float
 * @phpstan-type TemplateParameterMap array<string, string|int|float>
 */
final class TemplateParameters
{
    private function __construct()
    {
    }

    /**
     * @param array<array-key, mixed> $parameters
     *
     * @throws AdsefidValidationException if a key is not a string, or a value is
     *                                    not a string/int/float, or a float is
     *                                    not finite (NAN and INF have no JSON
     *                                    representation).
     */
    public static function validate(array $parameters, string $field = 'parameters'): void
    {
        foreach ($parameters as $name => $value) {
            if (!is_string($name)) {
                throw new AdsefidValidationException(
                    sprintf('"%s" keys must be parameter names; got a %s key.', $field, gettype($name)),
                    $field,
                );
            }

            if (is_float($value)) {
                if (!is_finite($value)) {
                    throw new AdsefidValidationException(
                        sprintf('"%s[%s]" must be a finite number.', $field, $name),
                        $field,
                    );
                }

                continue;
            }

            if (!is_string($value) && !is_int($value)) {
                throw new AdsefidValidationException(
                    sprintf(
                        '"%s[%s]" must be a string, int, or float; got %s. Pass a numeric string to keep leading zeros or an exact decimal.',
                        $field,
                        $name,
                        get_debug_type($value),
                    ),
                    $field,
                );
            }
        }
    }
}
