<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Resources;

use Adsefid\Sdk\Http\Transport;
use Adsefid\Sdk\Models\User\GetUserTemplatesRequest;
use Adsefid\Sdk\Models\User\GetUserTemplatesResponse;
use Adsefid\Sdk\Models\User\UserInfo;
use Adsefid\Sdk\Models\User\UserLine;
use Adsefid\Sdk\Models\User\UserProfile;

/**
 * Account/user endpoints (`/v1/user/*`). Every method throws
 * `Adsefid\Sdk\Exceptions\AdsefidApiException` (or its subclass
 * `AdsefidRateLimitException`) on a `status: "error"` response, and
 * `Adsefid\Sdk\Exceptions\AdsefidTransportException` on a network failure;
 * a success is always returned as its typed response DTO.
 */
final class UserResource
{
    public function __construct(
        private readonly Transport $transport,
    ) {
    }

    public function getInfo(): UserInfo
    {
        return $this->transport->map(UserInfo::fromArray(...), $this->transport->get('/v1/user/info'));
    }

    /**
     * @return UserLine[]
     */
    public function getLines(): array
    {
        return $this->transport->mapList(UserLine::fromArray(...), $this->transport->get('/v1/user/lines'));
    }

    /**
     * @return UserProfile[]
     */
    public function getProfiles(): array
    {
        return $this->transport->mapList(UserProfile::fromArray(...), $this->transport->get('/v1/user/profiles'));
    }

    public function getTemplates(GetUserTemplatesRequest $request): GetUserTemplatesResponse
    {
        return $this->transport->map(GetUserTemplatesResponse::fromArray(...), $this->transport->get('/v1/user/templates', $request->toQuery()));
    }
}
