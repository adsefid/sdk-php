<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\Validator;

final class CancelMessengerRequest
{
    private const MAX_COMBINED_IDS = 2000;

    /**
     * @param string[] $messageIds
     * @param string[] $localIds
     *
     * @throws AdsefidValidationException if id lists are empty or exceed 2000 distinct ids combined.
     */
    public function __construct(
        public readonly array $messageIds = [],
        public readonly array $localIds = [],
    ) {
        Validator::requireAtLeastOne([$this->messageIds, $this->localIds], ['message_ids', 'local_ids']);
        Validator::maxCount(count(array_unique($this->messageIds)) + count(array_unique($this->localIds)), self::MAX_COMBINED_IDS, 'message_ids+local_ids');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message_ids' => $this->messageIds,
            'local_ids' => $this->localIds,
        ];
    }
}
