<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests;

use Adsefid\Sdk\Enums\TemplateParameterType;
use Adsefid\Sdk\Enums\TemplateState;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Models\User\GetUserTemplatesRequest;
use Adsefid\Sdk\Tests\Support\TestClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UserResourceTest extends TestCase
{
    public function testGetInfo(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/user.get_info.success.json');

        $result = $client->user->getInfo();

        self::assertSame('GET', $http->only()->getMethod());
        self::assertSame('/v1/user/info', $http->only()->getUri()->getPath());
        self::assertNotSame('', $result->name);
    }

    public function testGetLines(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/user.get_lines.success.json');

        $result = $client->user->getLines();

        self::assertSame('/v1/user/lines', $http->only()->getUri()->getPath());
        self::assertNotEmpty($result);
    }

    public function testGetProfiles(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/user.get_profiles.success.json');

        $result = $client->user->getProfiles();

        self::assertSame('/v1/user/profiles', $http->only()->getUri()->getPath());
        self::assertNotEmpty($result);
    }

    public function testGetTemplatesParsesTheDocumentedShape(): void
    {
        [$client, $http] = TestClient::respondingWithFixture('envelopes/user.get_templates.success.json');

        $result = $client->user->getTemplates(new GetUserTemplatesRequest(
            state: TemplateState::Approved,
            skip: 0,
            take: 50,
        ));

        parse_str($http->only()->getUri()->getQuery(), $query);
        self::assertSame('approved', $query['state']);
        self::assertSame('0', $query['skip']);
        self::assertSame('50', $query['take']);

        self::assertSame(1, $result->total);
        self::assertCount(1, $result->items);
        $item = $result->items[0];
        self::assertSame(TemplateState::Approved, $item->state);
        self::assertSame(TemplateParameterType::String, $item->parameters['OTPCode']);
        self::assertSame(TemplateParameterType::Number, $item->parameters['amount']);
        self::assertNull($item->description);
    }

    /**
     * The live service emits a parameter type this SDK does not model.
     * Dropping the entry keeps the typed map honest rather than surfacing a
     * value callers cannot match on.
     */
    public function testGetTemplatesDropsUndocumentedParameterTypes(): void
    {
        [$client] = TestClient::respondingWithFixture('envelopes/user.get_templates.unknown_type.json');

        $result = $client->user->getTemplates(new GetUserTemplatesRequest());

        $parameters = $result->items[0]->parameters;
        self::assertArrayNotHasKey('link', $parameters);
        self::assertSame(['OTPCode', 'amount'], array_keys($parameters));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function validTakeProvider(): iterable
    {
        yield 'minimum' => [1];
        yield 'maximum' => [100];
    }

    #[DataProvider('validTakeProvider')]
    public function testTakeBoundariesAreAccepted(int $take): void
    {
        $request = new GetUserTemplatesRequest(take: $take);
        self::assertSame($take, $request->take);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function invalidPagingProvider(): iterable
    {
        yield 'take below the minimum' => [0, 0];
        yield 'take above the maximum' => [0, 101];
        yield 'negative skip' => [-1, 50];
    }

    #[DataProvider('invalidPagingProvider')]
    public function testOutOfRangePagingIsRejectedAtConstruction(int $skip, int $take): void
    {
        $this->expectException(AdsefidValidationException::class);
        new GetUserTemplatesRequest(skip: $skip, take: $take);
    }
}
