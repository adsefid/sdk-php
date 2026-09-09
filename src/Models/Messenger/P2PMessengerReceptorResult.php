<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Enums\WebServiceResponseCode;
use Adsefid\Sdk\Support\WebServiceCode;

/**
 * The outcome for one receptor of a P2P Messenger send (`SendP2PMessengerResponse::$receptors`).
 *
 * `statusCode` is the raw `WebServiceCode` (doc §3.3): `1000-1999` means the
 * item was accepted and `messageStatus` is set; `2000+` means this one item
 * was rejected and `errorCode` is set instead. Both views are `null` for a
 * code this SDK does not know yet, so decoding never fails on a new code.
 */
final class P2PMessengerReceptorResult
{
    public function __construct(
        public readonly ?string $messageId,
        public readonly string $receptor,
        public readonly int $statusCode,
        public readonly ?WebServiceMessageStatus $messageStatus,
        public readonly ?WebServiceResponseCode $errorCode,
        public readonly string $message,
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
        $statusCode = (int) $data['status'];

        return new self(
            messageId: isset($data['message_id']) ? (string) $data['message_id'] : null,
            receptor: (string) $data['receptor'],
            statusCode: $statusCode,
            messageStatus: WebServiceCode::messageStatus($statusCode),
            errorCode: WebServiceCode::errorCode($statusCode),
            message: (string) $data['message'],
            localId: isset($data['local_id']) ? (string) $data['local_id'] : null,
            hide: (bool) $data['hide'],
            cost: (float) $data['cost'],
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
            'message' => $this->message,
            'local_id' => $this->localId,
            'hide' => $this->hide,
            'cost' => $this->cost,
        ];
    }
}
