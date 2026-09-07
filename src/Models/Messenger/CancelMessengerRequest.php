<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class CancelMessengerRequest
{
    /**
     * @param string[] $messageIds
     * @param string[] $localIds
     *
     * @throws AdsefidValidationException if both `messageIds` and `localIds` are empty.
     */
    public function __construct(
        public readonly array $messageIds = [],
        public readonly array $localIds = [],
    ) {
        LocalIdValidator::requireAtLeastOne([$this->messageIds, $this->localIds], ['message_ids', 'local_ids']);
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
