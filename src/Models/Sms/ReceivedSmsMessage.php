<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

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
            message: (string) $data['message'],
            lineNumber: (string) $data['line_number'],
            receiveDate: new \DateTimeImmutable((string) $data['receive_date']),
            sender: (string) $data['sender'],
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
