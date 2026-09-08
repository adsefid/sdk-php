<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests\Support;

/**
 * Access to the golden fixtures, which are byte-identical copies of the same
 * tree in the sibling SDK repositories.
 */
final class Fixtures
{
    private function __construct()
    {
    }

    public static function dir(): string
    {
        return __DIR__ . '/../fixtures';
    }

    /**
     * Reads one fixture as raw bytes. Webhook verification signs the exact
     * bytes on the wire, so anything feeding a signature must come from here
     * rather than being re-encoded from a decoded array.
     */
    public static function bytes(string $name): string
    {
        $contents = file_get_contents(self::dir() . '/' . $name);
        if ($contents === false) {
            throw new \RuntimeException(sprintf('Fixture "%s" could not be read.', $name));
        }

        return $contents;
    }

    /**
     * @return array<array-key, mixed>
     */
    public static function json(string $name): array
    {
        $decoded = json_decode(self::bytes($name), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new \RuntimeException(sprintf('Fixture "%s" is not a JSON array or object.', $name));
        }

        return $decoded;
    }

    /**
     * @return list<array{sha256: string, name: string}>
     */
    public static function manifest(): array
    {
        $entries = [];
        foreach (explode("\n", trim(self::bytes('CHECKSUMS.txt'))) as $line) {
            [$sha256, $name] = explode('  ', $line, 2);
            $entries[] = ['sha256' => $sha256, 'name' => $name];
        }

        return $entries;
    }
}
