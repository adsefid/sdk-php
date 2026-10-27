<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Tests;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Models\Sms\GetSmsStatusRequest;
use Adsefid\Sdk\Models\Sms\SendTemplateSmsRequest;
use Adsefid\Sdk\Models\User\GetUserTemplatesRequest;
use Adsefid\Sdk\Tests\Support\TestClient;
use PHPUnit\Framework\TestCase;

/**
 * Fixtures shaped like real gateway responses: 7-digit fractional seconds, an empty-string
 * description, null account fields, long decimal money and "+98" receptors.
 */
final class LiveShapesTest extends TestCase
{
    public function testAccountInfoKeepsNullFields(): void
    {
        [$client] = TestClient::respondingWithFixture('envelopes/user.get_info.live.json');

        $info = $client->user->getInfo();

        self::assertNull($info->companyName);
        self::assertNull($info->email);
        self::assertSame(5877471300.0, $info->creditLeft);
    }

    public function testTemplatesParseSevenDigitTimestampsAndAnEmptyDescription(): void
    {
        [$client] = TestClient::respondingWithFixture('envelopes/user.get_templates.live.json');

        $item = $client->user->getTemplates(new GetUserTemplatesRequest())->items[0];

        self::assertSame('', $item->description);
        self::assertSame('2026-09-19T08:13:10Z', $item->createdAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'));
    }

    public function testTemplateSendKeepsANormalizedReceptorAndANullExpiry(): void
    {
        [$client] = TestClient::respondingWithFixture('envelopes/sms.send_template.live.json');

        $response = $client->sms->sendTemplate(new SendTemplateSmsRequest(
            templateId: 'otp',
            parameters: ['otp_code' => '001234'],
            receptor: '989120000000',
            lineNumber: '983000',
        ));

        self::assertSame('+989120000000', $response->receptor);
        self::assertNull($response->expiryDate);
        self::assertSame(1129.0, $response->cost);
    }

    public function testStatusParsesASevenDigitDeliveryTime(): void
    {
        [$client] = TestClient::respondingWithFixture('envelopes/sms.get_status.live.json');

        $item = $client->sms->getStatus(new GetSmsStatusRequest(localIds: ['order-10001']))->receptors[0];

        self::assertSame(WebServiceMessageStatus::Undelivered, $item->status);
        self::assertNotNull($item->deliveryTime);
    }
}
