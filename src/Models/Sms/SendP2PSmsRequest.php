<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class SendP2PSmsRequest
{
    /**
     * @param list<P2PSmsMessage> $messages
     *
     * @throws AdsefidValidationException if `messages` is empty or `line_number` is empty. Item errors are returned in the partial API response.
     */
    public function __construct(
        public readonly array $messages,
        public readonly string $lineNumber,
        public readonly ?LineSelector $lineSelector = null,
        public readonly ?\DateTimeImmutable $sendTime = null,
    ) {
        LocalIdValidator::requireNonEmptyArray($this->messages, 'messages');
        LocalIdValidator::requireNonEmpty($this->lineNumber, 'line_number');
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
