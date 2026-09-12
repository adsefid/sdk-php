<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Enums\WebServiceResponseCode;
use Adsefid\Sdk\Models\Sms\BulkSmsReceptor;
use Adsefid\Sdk\Models\Sms\P2PSmsMessage;
use Adsefid\Sdk\Support\WebServiceCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WebServiceCodeTest extends TestCase
{
    /**
     * @return iterable<string, array{int, ?WebServiceMessageStatus, ?WebServiceResponseCode}>
     */
    public static function codeProvider(): iterable
    {
        yield 'scheduled' => [1000, WebServiceMessageStatus::Scheduled, null];
        yield 'delivered' => [1002, WebServiceMessageStatus::Delivered, null];
        yield 'receptor blacklisted' => [2025, null, WebServiceResponseCode::ReceptorBlacklisted];
        yield 'invalid receptor' => [2014, null, WebServiceResponseCode::InvalidReceptor];
        yield 'invalid message ids' => [2046, null, WebServiceResponseCode::InvalidMessageIds];
        yield 'file too large' => [2047, null, WebServiceResponseCode::FileTooLarge];
        // Codes this SDK does not know yet map to neither view.
        yield 'unknown status' => [1500, null, null];
        yield 'unknown error' => [2999, null, null];
        yield 'zero' => [0, null, null];
    }

    public function testV113ResponseCodeHttpStatuses(): void
    {
        self::assertSame(400, WebServiceResponseCode::InvalidMessageIds->httpStatus());
        self::assertSame(413, WebServiceResponseCode::FileTooLarge->httpStatus());
    }

    #[DataProvider('codeProvider')]
    public function testSplitsACodeByRange(int $code, ?WebServiceMessageStatus $status, ?WebServiceResponseCode $error): void
    {
        self::assertSame($status, WebServiceCode::messageStatus($code));
        self::assertSame($error, WebServiceCode::errorCode($code));
        self::assertSame($code >= 1000 && $code < 2000, WebServiceCode::isMessageStatus($code));
        self::assertSame($code >= 2000, WebServiceCode::isErrorCode($code));
    }

    public function testRequestItemsOmitAnUnsetLocalId(): void
    {
        self::assertSame(['receptor' => 'a', 'hide' => false], (new BulkSmsReceptor('a'))->toArray());
        self::assertSame(
            ['receptor' => 'a', 'message' => 'm', 'hide' => true, 'local_id' => 'x-1'],
            (new P2PSmsMessage('a', 'm', localId: 'x-1', hide: true))->toArray(),
        );
    }
}
