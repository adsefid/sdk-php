<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests;

use Adsefid\Sdk\Tests\Support\Fixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The golden fixtures are byte-identical copies of the same tree in the sibling
 * SDK repositories. One that drifts here silently weakens every test that reads
 * it, so the manifest is verified rather than trusted.
 */
final class FixturesIntegrityTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function manifestProvider(): iterable
    {
        foreach (Fixtures::manifest() as $entry) {
            yield $entry['name'] => [$entry['name'], $entry['sha256']];
        }
    }

    #[DataProvider('manifestProvider')]
    public function testFixtureMatchesItsChecksum(string $name, string $expectedSha256): void
    {
        self::assertFileExists(Fixtures::dir() . '/' . $name);
        self::assertSame($expectedSha256, hash('sha256', Fixtures::bytes($name)));
    }

    public function testNoFixtureIsMissingFromTheManifest(): void
    {
        $listed = array_column(Fixtures::manifest(), 'name');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(Fixtures::dir(), \FilesystemIterator::SKIP_DOTS),
        );

        $onDisk = [];
        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->getFilename() === 'CHECKSUMS.txt') {
                continue;
            }
            $onDisk[] = str_replace(Fixtures::dir() . '/', '', $file->getPathname());
        }

        sort($listed);
        sort($onDisk);
        self::assertSame($listed, $onDisk);
    }
}
