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
        return UserInfo::fromArray($this->transport->get('/v1/user/info'));
    }

    /**
     * @return UserLine[]
     */
    public function getLines(): array
    {
        $data = $this->transport->get('/v1/user/lines');
        $lines = $this->extractList($data);

        return array_map(static fn (array $line): UserLine => UserLine::fromArray($line), $lines);
    }

    /**
     * @return UserProfile[]
     */
    public function getProfiles(): array
    {
        $data = $this->transport->get('/v1/user/profiles');
        $profiles = $this->extractList($data);

        return array_map(static fn (array $profile): UserProfile => UserProfile::fromArray($profile), $profiles);
    }

    public function getTemplates(GetUserTemplatesRequest $request): GetUserTemplatesResponse
    {
        return GetUserTemplatesResponse::fromArray($this->transport->get('/v1/user/templates', $request->toQuery()));
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractList(array $data): array
    {
        // `data` for these endpoints is a top-level JSON array; Transport wraps
        // it as `['data' => [...]]` when it cannot treat it as an object map.
        if (array_is_list($data)) {
            return array_map(
                static fn (mixed $item): array => is_array($item) ? $item : [],
                $data,
            );
        }

        return is_array($data['data'] ?? null) ? $data['data'] : [];
    }
}
