<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Enums\WebServiceResponseCode;
use Adsefid\Sdk\Support\WebServiceCode;
use Adsefid\Sdk\Support\Wire;

/**
 * The outcome for one receptor of a bulk Messenger send (`SendBulkMessengerResponse::$receptors`).
 *
 * `statusCode` is the raw `WebServiceCode` (doc §3.3): `1000-1999` means the
 * item was accepted and `messageStatus` is set; `2000+` means this one item
 * was rejected and `errorCode` is set instead. Both views are `null` for a
 * code this SDK does not know yet, so decoding never fails on a new code.
 */
final class BulkMessengerReceptorResult
{
    public function __construct(
        public readonly ?string $messageId,
        public readonly string $receptor,
        public readonly int $statusCode,
        public readonly ?WebServiceMessageStatus $messageStatus,
        public readonly ?WebServiceResponseCode $errorCode,
        public readonly ?string $localId,
        public readonly bool $hide,
        public readonly float $cost,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $statusCode = Wire::int($data, 'status');

        return new self(
            messageId: isset($data['message_id']) ? Wire::string($data, 'message_id') : null,
            receptor: Wire::string($data, 'receptor'),
            statusCode: $statusCode,
            messageStatus: WebServiceCode::messageStatus($statusCode),
            errorCode: WebServiceCode::errorCode($statusCode),
            localId: isset($data['local_id']) ? Wire::string($data, 'local_id') : null,
            hide: Wire::bool($data, 'hide'),
            cost: Wire::float($data, 'cost'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message_id' => $this->messageId,
            'receptor' => $this->receptor,
            'status' => $this->statusCode,
            'local_id' => $this->localId,
            'hide' => $this->hide,
            'cost' => $this->cost,
        ];
    }
}
