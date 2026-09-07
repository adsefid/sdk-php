<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Http\CsvJoiner;
use Adsefid\Sdk\Support\LocalIdValidator;

final class GetMessengerStatusRequest
{
    private const MAX_COMBINED_IDS = 2000;

    /**
     * @param string[] $messageIds
     * @param string[] $localIds
     *
     * @throws AdsefidValidationException if both `messageIds` and `localIds` are empty, or their combined count exceeds 2000.
     */
    public function __construct(
        public readonly array $messageIds = [],
        public readonly array $localIds = [],
    ) {
        LocalIdValidator::requireAtLeastOne([$this->messageIds, $this->localIds], ['message_ids', 'local_ids']);
        LocalIdValidator::maxCount(count(array_unique($this->messageIds)) + count(array_unique($this->localIds)), self::MAX_COMBINED_IDS, 'message_ids+local_ids');
    }

    /**
     * @return array<string, string|null>
     */
    public function toQuery(): array
    {
        return [
            'message_ids' => CsvJoiner::join($this->messageIds),
            'local_ids' => CsvJoiner::join($this->localIds),
        ];
    }
}
