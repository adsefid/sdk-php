<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class SendP2PSmsRequest
{
    private const MESSAGE_MAX_LENGTH = 900;

    /**
     * @param list<P2PSmsMessage> $messages
     *
     * @throws AdsefidValidationException if `messages` is empty, a required field is empty, any `messages[].message` exceeds 900 characters, or any `messages[].local_id` doesn't match the required pattern.
     */
    public function __construct(
        public readonly array $messages,
        public readonly string $lineNumber,
        public readonly ?LineSelector $lineSelector = null,
        public readonly ?\DateTimeImmutable $sendTime = null,
    ) {
        LocalIdValidator::requireNonEmptyArray($this->messages, 'messages');
        LocalIdValidator::requireNonEmpty($this->lineNumber, 'line_number');

        foreach ($this->messages as $index => $message) {
            LocalIdValidator::requireNonEmpty($message->receptor, sprintf('messages[%d].receptor', $index));
            $body = LocalIdValidator::requireNonEmpty($message->message, sprintf('messages[%d].message', $index));
            LocalIdValidator::maxLength($body, self::MESSAGE_MAX_LENGTH, sprintf('messages[%d].message', $index));
            LocalIdValidator::validate($message->localId, sprintf('messages[%d].local_id', $index));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'messages' => array_map(static fn (P2PSmsMessage $message): array => $message->toArray(), $this->messages),
            'line_number' => $this->lineNumber,
        ];

        if ($this->lineSelector !== null) {
            $data['line_selector'] = $this->lineSelector->value;
        }

        if ($this->sendTime !== null) {
            $data['send_time'] = $this->sendTime->format(DATE_ATOM);
        }

        return $data;
    }
}
