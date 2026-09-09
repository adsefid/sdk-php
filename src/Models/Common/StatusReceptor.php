<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Common;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Support\WebServiceCode;

/**
 * One message in a get-status response, for SMS and Messenger alike.
 *
 * `statusCode` is the raw value; `status` is its typed view and is `null`
 * for a code this SDK does not know yet, so a new server-side status never
 * fails the whole call.
 */
final class StatusReceptor
{
    public function __construct(
        public readonly string $messageId,
        public readonly ?string $localId,
        public readonly int $statusCode,
        public readonly ?WebServiceMessageStatus $status,
        public readonly string $receptor,
        public readonly ?\DateTimeImmutable $sendTime,
        public readonly ?\DateTimeImmutable $deliveryTime,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $statusCode = (int) $data['status'];

        return new self(
            messageId: (string) $data['message_id'],
            localId: isset($data['local_id']) ? (string) $data['local_id'] : null,
            statusCode: $statusCode,
            status: WebServiceCode::messageStatus($statusCode),
            receptor: (string) $data['receptor'],
            sendTime: isset($data['send_time']) ? new \DateTimeImmutable((string) $data['send_time']) : null,
            deliveryTime: isset($data['delivery_time']) ? new \DateTimeImmutable((string) $data['delivery_time']) : null,
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
            'receptor' => $this->receptor,
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'delivery_time' => $this->deliveryTime?->format(DATE_ATOM),
        ];
    }
}
