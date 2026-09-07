<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Http;

final class CsvJoiner
{
    private function __construct()
    {
    }

    /**
     * @param string[] $values
     */
    public static function join(array $values): ?string
    {
        if ($values === []) {
            return null;
        }

        return implode(',', $values);
    }
}
