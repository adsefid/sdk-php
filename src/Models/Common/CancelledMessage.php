<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Common;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Support\WebServiceCode;

/**
 * One entry of a cancel response's `cancelled_messages` or `failed_to_cancel`
 * list. `status` is `null` for a status code this SDK does not know yet;
 * `statusCode` always holds the raw value.
 */
final class CancelledMessage
{
    public function __construct(
        public readonly string $messageId,
        public readonly ?string $localId,
        public readonly int $statusCode,
        public readonly ?WebServiceMessageStatus $status,
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
