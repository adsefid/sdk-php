<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Webhooks;

/**
 * One inbound SMS carried by a `ReceiveWebhookEvent`.
 */
final readonly class ReceivedMessageItem
{
    public function __construct(
        public int $id,
        public string $lineNumber,
        public string $sender,
        public string $message,
        public \DateTimeImmutable $receiveDate,
    ) {
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: (int) $item['id'],
            lineNumber: (string) $item['line_number'],
            sender: (string) $item['sender'],
            message: (string) $item['message'],
            receiveDate: new \DateTimeImmutable((string) $item['receive_date']),
        );
    }
}
