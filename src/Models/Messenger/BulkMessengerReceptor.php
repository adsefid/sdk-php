<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

/**
 * One receptor of a bulk Messenger send (`SendBulkMessengerRequest::$receptors`).
 *
 * Item validation happens server-side so valid entries can still be accepted
 * when another entry is rejected.
 */
final class BulkMessengerReceptor
{
    public function __construct(
        public readonly string $receptor,
        public readonly ?string $localId = null,
        public readonly bool $hide = false,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'receptor' => $this->receptor,
            'hide' => $this->hide,
        ];

        if ($this->localId !== null) {
            $data['local_id'] = $this->localId;
        }

        return $data;
    }
}
