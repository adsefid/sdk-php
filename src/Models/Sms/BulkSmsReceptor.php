<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

/**
 * One receptor of a bulk SMS send (`SendBulkSmsRequest::$receptors`).
 *
 * Validation of every item (non-empty fields, message length, `local_id`
 * shape) happens in the enclosing request's constructor, so the reported
 * field name carries the item's index.
 */
final class BulkSmsReceptor
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
