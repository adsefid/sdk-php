<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Webhooks;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;
use Adsefid\Sdk\Support\WebServiceCode;

/**
 * One message's delivery-status change, carried by a `StatusWebhookEvent`
 * (SMS) or a `MessengerStatusWebhookEvent`. `statusDelivery` is `null` for
 * a status code this SDK does not know yet; `statusDeliveryCode` always
 * holds the raw value.
 */
final readonly class StatusUpdateItem
{
    public function __construct(
        public string $id,
        public ?string $localId,
        public int $statusDeliveryCode,
        public ?WebServiceMessageStatus $statusDelivery,
        public ?\DateTimeImmutable $deliveryTime,
    ) {
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function fromArray(array $item): self
    {
        $statusDeliveryCode = (int) $item['status_delivery'];

        return new self(
            id: (string) $item['id'],
            localId: isset($item['local_id']) ? (string) $item['local_id'] : null,
            statusDeliveryCode: $statusDeliveryCode,
            statusDelivery: WebServiceCode::messageStatus($statusDeliveryCode),
            deliveryTime: isset($item['delivery_time']) ? new \DateTimeImmutable((string) $item['delivery_time']) : null,
        );
    }
}
