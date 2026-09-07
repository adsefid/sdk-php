<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Resources;

use Adsefid\Sdk\Http\MultipartStreamBuilder;
use Adsefid\Sdk\Http\Transport;
use Adsefid\Sdk\Models\Messenger\CancelMessengerRequest;
use Adsefid\Sdk\Models\Messenger\CancelMessengerResponse;
use Adsefid\Sdk\Models\Messenger\GetMessengerStatusRequest;
use Adsefid\Sdk\Models\Messenger\GetMessengerStatusResponse;
use Adsefid\Sdk\Models\Messenger\SendBulkMessengerRequest;
use Adsefid\Sdk\Models\Messenger\SendBulkMessengerResponse;
use Adsefid\Sdk\Models\Messenger\SendP2PMessengerRequest;
use Adsefid\Sdk\Models\Messenger\SendP2PMessengerResponse;
use Adsefid\Sdk\Models\Messenger\SendSingleMessengerRequest;
use Adsefid\Sdk\Models\Messenger\SendSingleMessengerResponse;
use Adsefid\Sdk\Models\Messenger\SendTemplateMessengerRequest;
use Adsefid\Sdk\Models\Messenger\SendTemplateMessengerResponse;
use Adsefid\Sdk\Models\Messenger\UploadMessengerFileRequest;
use Adsefid\Sdk\Models\Messenger\UploadMessengerFileResponse;

/**
 * Messenger endpoints (`/v1/messenger/*`, Rubika/Bale/etc.). Every method
 * throws `Adsefid\Sdk\Exceptions\AdsefidApiException` (or its subclass
 * `AdsefidRateLimitException`) on a `status: "error"` response, and
 * `Adsefid\Sdk\Exceptions\AdsefidTransportException` on a network failure;
 * a success is always returned as its typed response DTO.
 */
final class MessengerResource
{
    public function __construct(
        private readonly Transport $transport,
    ) {
    }

    public function sendSingle(SendSingleMessengerRequest $request): SendSingleMessengerResponse
    {
        return SendSingleMessengerResponse::fromArray($this->transport->post('/v1/messenger/single', $request->toArray()));
    }

    public function sendBulk(SendBulkMessengerRequest $request): SendBulkMessengerResponse
    {
        return SendBulkMessengerResponse::fromArray($this->transport->post('/v1/messenger/bulk', $request->toArray()));
    }

    public function sendP2P(SendP2PMessengerRequest $request): SendP2PMessengerResponse
    {
        return SendP2PMessengerResponse::fromArray($this->transport->post('/v1/messenger/p2p', $request->toArray()));
    }

    public function uploadFile(UploadMessengerFileRequest $request): UploadMessengerFileResponse
    {
        $builder = new MultipartStreamBuilder($this->transport->getStreamFactory());

        $body = $builder->buildSingleFile('file', $request->filename, $request->contentType, $request->stream);

        return UploadMessengerFileResponse::fromArray(
            $this->transport->postMultipart('/v1/messenger/file', $body, $builder->getContentType()),
        );
    }

    public function cancel(CancelMessengerRequest $request): CancelMessengerResponse
    {
        return CancelMessengerResponse::fromArray($this->transport->post('/v1/messenger/cancel', $request->toArray()));
    }

    public function sendTemplate(SendTemplateMessengerRequest $request): SendTemplateMessengerResponse
    {
        return SendTemplateMessengerResponse::fromArray($this->transport->post('/v1/messenger/template', $request->toArray()));
    }

    public function getStatus(GetMessengerStatusRequest $request): GetMessengerStatusResponse
    {
        return GetMessengerStatusResponse::fromArray($this->transport->get('/v1/messenger/status', $request->toQuery()));
    }
}
