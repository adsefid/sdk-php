<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;
use Adsefid\Sdk\Support\TemplateParameters;
use Adsefid\Sdk\Tests\Support\Fixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    /**
     * The golden table is shared byte-for-byte with the sibling SDK
     * repositories, so all five agree on what a local_id may be.
     *
     * @return iterable<string, array{string, bool}>
     */
    public static function localIdProvider(): iterable
    {
        /** @var list<array{value: string, valid: bool, why: string}> $cases */
        $cases = Fixtures::json('validation/local_ids.json');
        foreach ($cases as $case) {
            yield $case['why'] => [$case['value'], $case['valid']];
        }
    }

    #[DataProvider('localIdProvider')]
    public function testLocalIdGoldenTable(string $value, bool $valid): void
    {
        if ($valid) {
            $this->expectNotToPerformAssertions();
        } else {
            $this->expectException(AdsefidValidationException::class);
        }

        LocalIdValidator::validate($value);
    }

    public function testANullLocalIdIsNotSupplied(): void
    {
        $this->expectNotToPerformAssertions();
        LocalIdValidator::validate(null);
    }

    public function testTheFieldNameReachesTheException(): void
    {
        try {
            LocalIdValidator::validate('-bad', 'receptors[0].local_id');
            self::fail('expected a validation exception');
        } catch (AdsefidValidationException $exception) {
            self::assertStringContainsString('receptors[0].local_id', $exception->getMessage());
        }
    }

    /**
     * The platform counts its length limits in UTF-16 code units, so a
     * character outside the Basic Multilingual Plane costs two. `mb_strlen`
     * counts code points and would accept a message the platform rejects.
     *
     * @return iterable<string, array{string, int}>
     */
    public static function utf16LengthProvider(): iterable
    {
        yield 'empty' => ['', 0];
        yield 'ascii' => ['abc', 3];
        yield 'persian' => ['سلام', 4];
        yield 'one emoji is a surrogate pair' => ['😀', 2];
        yield 'mixed' => ['a😀b', 4];
        yield 'precomposed accent' => ['é', 1];
    }

    #[DataProvider('utf16LengthProvider')]
    public function testUtf16Length(string $value, int $expected): void
    {
        self::assertSame($expected, LocalIdValidator::utf16Length($value));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function messageLengthProvider(): iterable
    {
        yield 'ascii at the limit' => [str_repeat('a', 900), false];
        yield 'ascii one over' => [str_repeat('a', 901), true];
        yield 'persian at the limit' => [str_repeat('س', 900), false];
        yield 'persian one over' => [str_repeat('س', 901), true];
        yield '450 emoji is exactly 900 code units' => [str_repeat('😀', 450), false];
        yield '451 emoji is 902 code units' => [str_repeat('😀', 451), true];
        yield '900 emoji is 1800 code units' => [str_repeat('😀', 900), true];
    }

    #[DataProvider('messageLengthProvider')]
    public function testMaxLengthCountsUtf16CodeUnits(string $value, bool $rejected): void
    {
        if ($rejected) {
            $this->expectException(AdsefidValidationException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        LocalIdValidator::maxLength($value, 900, 'message');
    }

    public function testTheSharedLimitsTableMatchesWhatThisSdkEnforces(): void
    {
        /** @var array<string, int|string> $limits */
        $limits = Fixtures::json('validation/limits.json');

        self::assertSame(900, $limits['sms_message_max_length']);
        self::assertSame(4000, $limits['messenger_message_max_length']);
        self::assertSame(2000, $limits['combined_status_ids_max']);
        self::assertSame(1, $limits['receive_count_min']);
        self::assertSame(499, $limits['receive_count_max']);
        self::assertSame(1, $limits['templates_take_min']);
        self::assertSame(100, $limits['templates_take_max']);
        self::assertSame(36, $limits['local_id_max_length']);
    }

    public function testRequireNonEmpty(): void
    {
        self::assertSame('x', LocalIdValidator::requireNonEmpty('x', 'field'));

        $this->expectException(AdsefidValidationException::class);
        LocalIdValidator::requireNonEmpty('', 'field');
    }

    public function testRequireNonEmptyArray(): void
    {
        LocalIdValidator::requireNonEmptyArray(['a'], 'field');

        $this->expectException(AdsefidValidationException::class);
        LocalIdValidator::requireNonEmptyArray([], 'field');
    }

    public function testMaxCountIsInclusive(): void
    {
        LocalIdValidator::maxCount(2000, 2000, 'ids');

        $this->expectException(AdsefidValidationException::class);
        LocalIdValidator::maxCount(2001, 2000, 'ids');
    }

    public function testIntRangeAcceptsTheInclusiveBounds(): void
    {
        $this->expectNotToPerformAssertions();
        foreach ([1, 250, 499] as $value) {
            LocalIdValidator::intRange($value, 1, 499, 'count');
        }
    }

    public function testIntRangeRejectsOutsideTheBounds(): void
    {
        $this->expectException(AdsefidValidationException::class);
        LocalIdValidator::intRange(500, 1, 499, 'count');
    }

    public function testRequireAtLeastOneAcceptsAnyPopulatedList(): void
    {
        $this->expectNotToPerformAssertions();
        LocalIdValidator::requireAtLeastOne([['a'], null], ['message_ids', 'local_ids']);
    }

    public function testRequireAtLeastOneRejectsWhenEveryListIsEmpty(): void
    {
        $this->expectException(AdsefidValidationException::class);
        LocalIdValidator::requireAtLeastOne([null, []], ['message_ids', 'local_ids']);
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>, bool}>
     */
    public static function templateParameterProvider(): iterable
    {
        yield 'strings' => [['invoice' => '001234'], false];
        yield 'ints' => [['count' => 2], false];
        yield 'floats' => [['rate' => 19.99], false];
        yield 'a mixture' => [['a' => '001234', 'b' => 2, 'c' => 1.5], false];
        yield 'empty' => [[], false];
        yield 'a bool value' => [['flag' => true], true];
        yield 'a null value' => [['nothing' => null], true];
        yield 'a nested array' => [['nested' => ['a' => 1]], true];
        yield 'an object value' => [['obj' => new \stdClass()], true];
        yield 'a non-finite float' => [['nan' => NAN], true];
        yield 'an infinite float' => [['inf' => INF], true];
        yield 'a numeric key' => [[0 => 'positional'], true];
    }

    /**
     * @param array<array-key, mixed> $parameters
     */
    #[DataProvider('templateParameterProvider')]
    public function testTemplateParameterValidation(array $parameters, bool $rejected): void
    {
        if ($rejected) {
            $this->expectException(AdsefidValidationException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        TemplateParameters::validate($parameters);
    }

    /**
     * A number-typed parameter may travel as a JSON string, which is how
     * leading zeros and exact decimals survive: the platform substitutes a
     * numeric string verbatim.
     */
    public function testTheSharedTemplateExampleSerializesAsTheOtherSdksDo(): void
    {
        /** @var array{parameters: array<string, string|int|float>, expected_json: string} $example */
        $example = Fixtures::json('validation/template_parameters.json');

        $parameters = $example['parameters'];
        TemplateParameters::validate($parameters);

        // The fixture's canonical form is key-sorted; PHP preserves insertion
        // order, so sort before comparing. What matters is that the values
        // encode identically in all five SDKs, not the key order.
        ksort($parameters);
        $encoded = json_encode($parameters, JSON_UNESCAPED_SLASHES);

        self::assertIsString($encoded);
        self::assertSame($example['expected_json'], $encoded);
    }
}
