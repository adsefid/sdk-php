<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

final class GetReceivedSmsResponse
{
    /**
     * @param array<int, array{message: string, line_number: string, receive_date: \DateTimeImmutable, sender: string}> $messages
     */
    public function __construct(
        public readonly array $messages,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            messages: array_map(
                static fn (array $message): array => [
                    'message' => (string) $message['message'],
                    'line_number' => (string) $message['line_number'],
                    'receive_date' => new \DateTimeImmutable((string) $message['receive_date']),
                    'sender' => (string) $message['sender'],
                ],
                (array) ($data['messages'] ?? []),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'messages' => array_map(
                static fn (array $message): array => [
                    'message' => $message['message'],
                    'line_number' => $message['line_number'],
                    'receive_date' => $message['receive_date']->format(DATE_ATOM),
                    'sender' => $message['sender'],
                ],
                $this->messages,
            ),
        ];
    }
}
