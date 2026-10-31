<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Models\Messenger\CancelMessengerRequest;
use Adsefid\Sdk\Models\Sms\CancelSmsRequest;
use Adsefid\Sdk\Tests\Support\TestClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CancellationTest extends TestCase
{
    /** @return iterable<string, array{string, int, int, int, bool}> */
    public static function cancellationCases(): iterable
    {
        foreach (['sms', 'messenger'] as $area) {
            foreach ([
                [0, 0, 1, true],
                [2000, 0, 1, false],
                [2001, 0, 1, true],
                [0, 2000, 1, false],
                [0, 2001, 1, true],
                [1000, 1000, 1, false],
                [1000, 1001, 1, true],
                [1000, 1000, 3, false],
            ] as [$messageCount, $localCount, $repetitions, $rejected]) {
                yield "$area-$messageCount-$localCount-$repetitions" => [$area, $messageCount, $localCount, $repetitions, $rejected];
            }
        }
    }

    #[DataProvider('cancellationCases')]
    public function testCancellationCountsDistinctIdsAndPreservesBody(string $area, int $messageCount, int $localCount, int $repetitions, bool $rejected): void
    {
        $messageIds = [];
        $localIds = [];
        for ($repeat = 0; $repeat < $repetitions; $repeat++) {
            for ($i = 0; $i < $messageCount; $i++) {
                $messageIds[] = sprintf('00000000-0000-0000-0000-%012d', $i);
            }
            for ($i = 0; $i < $localCount; $i++) {
                $localIds[] = sprintf('00000000-0000-0000-0000-%012d', $i);
            }
        }
        [$client, $http] = TestClient::respondingWithFixture("envelopes/$area.cancel.success.json");

        try {
            if ($area === 'sms') {
                $client->sms->cancel(new CancelSmsRequest($messageIds, $localIds));
            } else {
                $client->messenger->cancel(new CancelMessengerRequest($messageIds, $localIds));
            }
            self::assertFalse($rejected, 'Expected local validation to reject before sending.');
            self::assertSame('POST', $http->only()->getMethod());
            self::assertSame("/v1/$area/cancel", $http->only()->getUri()->getPath());
            self::assertSame(
                ['message_ids' => $messageIds, 'local_ids' => $localIds],
                json_decode((string) $http->only()->getBody(), true, flags: JSON_THROW_ON_ERROR),
            );
        } catch (AdsefidValidationException $exception) {
            self::assertTrue($rejected, $exception->getMessage());
            self::assertSame(0, $http->count());
        }
    }
    public function testCancellationPreservesNullableIdsAndItemErrors(): void
    {
        foreach (['sms', 'messenger'] as $area) {
            [$client, $http] = TestClient::respondingWithFixture('envelopes/cancel.partial_success.json');
            $result = $area === 'sms'
                ? $client->sms->cancel(new CancelSmsRequest(localIds: ['missing-local']))
                : $client->messenger->cancel(new CancelMessengerRequest(localIds: ['missing-local']));
            self::assertSame(\Adsefid\Sdk\Enums\WebServiceMessageStatus::Canceled, $result->cancelledMessages[0]->status);
            self::assertSame([1001, 2046, 2029, 2999], array_map(static fn ($item) => $item->statusCode, $result->failedToCancel));
            self::assertNull($result->failedToCancel[1]->localId);
            self::assertNull($result->failedToCancel[2]->messageId);
            self::assertSame(\Adsefid\Sdk\Enums\WebServiceResponseCode::InvalidMessageIds, $result->failedToCancel[1]->errorCode);
            self::assertSame(\Adsefid\Sdk\Enums\WebServiceResponseCode::InvalidLocalIds, $result->failedToCancel[2]->errorCode);
            self::assertNull($result->failedToCancel[3]->errorCode);
            self::assertSame(1, $http->count());
        }
    }
}
