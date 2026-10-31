<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Common;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Enums\WebServiceResponseCode;
use Adsefid\Sdk\Support\WebServiceCode;
use Adsefid\Sdk\Support\Wire;

/**
 * One entry of a cancel response's `cancelled_messages` or `failed_to_cancel`
 * list. `status` is `null` for a status code this SDK does not know yet;
 * `statusCode` always holds the raw value. An unmatched local ID has a null
 * `messageId`; unmatched identifiers expose their typed `errorCode`.
 */
final class CancelledMessage
{
    public function __construct(
        public readonly ?string $messageId,
        public readonly ?string $localId,
        public readonly int $statusCode,
        public readonly ?WebServiceMessageStatus $status,
        public readonly ?WebServiceResponseCode $errorCode = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $statusCode = Wire::int($data, 'status');

        return new self(
            messageId: array_key_exists('message_id', $data) && $data['message_id'] === null ? null : Wire::string($data, 'message_id'),
            localId: isset($data['local_id']) ? Wire::string($data, 'local_id') : null,
            statusCode: $statusCode,
            status: WebServiceCode::messageStatus($statusCode),
            errorCode: WebServiceCode::errorCode($statusCode),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message_id' => $this->messageId,
            'local_id' => $this->localId,
            'status' => $this->statusCode,
        ];
    }
}
