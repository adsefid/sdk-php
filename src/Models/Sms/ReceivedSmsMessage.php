<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Support\Wire;

/**
 * One inbound SMS in a `GetReceivedSmsResponse`.
 */
final class ReceivedSmsMessage
{
    public function __construct(
        public readonly string $message,
        public readonly string $lineNumber,
        public readonly \DateTimeImmutable $receiveDate,
        public readonly string $sender,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            message: Wire::string($data, 'message'),
            lineNumber: Wire::string($data, 'line_number'),
            receiveDate: Wire::date($data, 'receive_date'),
            sender: Wire::string($data, 'sender'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'line_number' => $this->lineNumber,
            'receive_date' => $this->receiveDate->format(DATE_ATOM),
            'sender' => $this->sender,
        ];
    }
}
