<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

final class GetReceivedSmsResponse
{
    /**
     * @param list<ReceivedSmsMessage> $messages
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
                static fn (array $message): ReceivedSmsMessage => ReceivedSmsMessage::fromArray($message),
                array_values((array) ($data['messages'] ?? [])),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'messages' => array_map(static fn (ReceivedSmsMessage $message): array => $message->toArray(), $this->messages),
        ];
    }
}
