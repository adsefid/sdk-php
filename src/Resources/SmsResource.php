<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Resources;

use Adsefid\Sdk\Http\Transport;
use Adsefid\Sdk\Models\Sms\CancelSmsRequest;
use Adsefid\Sdk\Models\Sms\CancelSmsResponse;
use Adsefid\Sdk\Models\Sms\GetReceivedSmsRequest;
use Adsefid\Sdk\Models\Sms\GetReceivedSmsResponse;
use Adsefid\Sdk\Models\Sms\GetSmsStatusRequest;
use Adsefid\Sdk\Models\Sms\GetSmsStatusResponse;
use Adsefid\Sdk\Models\Sms\SendBulkSmsRequest;
use Adsefid\Sdk\Models\Sms\SendBulkSmsResponse;
use Adsefid\Sdk\Models\Sms\SendP2PSmsRequest;
use Adsefid\Sdk\Models\Sms\SendP2PSmsResponse;
use Adsefid\Sdk\Models\Sms\SendSingleSmsRequest;
use Adsefid\Sdk\Models\Sms\SendSingleSmsResponse;
use Adsefid\Sdk\Models\Sms\SendTemplateSmsRequest;
use Adsefid\Sdk\Models\Sms\SendTemplateSmsResponse;

/**
 * SMS endpoints (`/v1/sms/*`). Every method throws
 * `Adsefid\Sdk\Exceptions\AdsefidApiException` (or its subclass
 * `AdsefidRateLimitException`) on a `status: "error"` response, and
 * `Adsefid\Sdk\Exceptions\AdsefidTransportException` on a network failure;
 * a success is always returned as its typed response DTO.
 */
final class SmsResource
{
    public function __construct(
        private readonly Transport $transport,
    ) {
    }

    public function sendSingle(SendSingleSmsRequest $request): SendSingleSmsResponse
    {
        return SendSingleSmsResponse::fromArray($this->transport->post('/v1/sms/single', $request->toArray()));
    }

    public function sendBulk(SendBulkSmsRequest $request): SendBulkSmsResponse
    {
        return SendBulkSmsResponse::fromArray($this->transport->post('/v1/sms/bulk', $request->toArray()));
    }

    public function sendP2P(SendP2PSmsRequest $request): SendP2PSmsResponse
    {
        return SendP2PSmsResponse::fromArray($this->transport->post('/v1/sms/p2p', $request->toArray()));
    }

    public function sendTemplate(SendTemplateSmsRequest $request): SendTemplateSmsResponse
    {
        return SendTemplateSmsResponse::fromArray($this->transport->post('/v1/sms/template', $request->toArray()));
    }

    public function getStatus(GetSmsStatusRequest $request): GetSmsStatusResponse
    {
        return GetSmsStatusResponse::fromArray($this->transport->get('/v1/sms/status', $request->toQuery()));
    }

    public function cancel(CancelSmsRequest $request): CancelSmsResponse
    {
        return CancelSmsResponse::fromArray($this->transport->post('/v1/sms/cancel', $request->toArray()));
    }

    public function getReceived(GetReceivedSmsRequest $request): GetReceivedSmsResponse
    {
        return GetReceivedSmsResponse::fromArray($this->transport->get('/v1/sms/receive', $request->toQuery()));
    }
}
