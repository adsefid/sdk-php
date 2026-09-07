<?php

declare(strict_types=1);

namespace Adsefid\Sdk;

use Adsefid\Sdk\Http\Transport;
use Adsefid\Sdk\Resources\MessengerResource;
use Adsefid\Sdk\Resources\SmsResource;
use Adsefid\Sdk\Resources\UserResource;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Entry point for the adsefid.com SMS Web Service API: builds a shared
 * `Transport` and exposes it through the `sms`, `messenger`, and `user`
 * resources.
 */
final class AdsefidClient
{
    public readonly SmsResource $sms;

    public readonly MessengerResource $messenger;

    public readonly UserResource $user;

    /**
     * @param ClientInterface|null        $httpClient      PSR-18 HTTP client. Omit to auto-detect one via `php-http/discovery`.
     * @param RequestFactoryInterface|null $requestFactory PSR-17 request factory. Omit to auto-detect.
     * @param StreamFactoryInterface|null  $streamFactory  PSR-17 stream factory. Omit to auto-detect.
     */
    public function __construct(
        ClientConfig $config,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $httpClient ??= Psr18ClientDiscovery::find();
        $requestFactory ??= Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory ??= Psr17FactoryDiscovery::findStreamFactory();

        $transport = new Transport(
            $httpClient,
            $requestFactory,
            $streamFactory,
            $config->baseUrl,
            $config->apiKey,
        );

        $this->sms = new SmsResource($transport);
        $this->messenger = new MessengerResource($transport);
        $this->user = new UserResource($transport);
    }
}
