<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class SendBulkSmsRequest
{
    private const MESSAGE_MAX_LENGTH = 900;

    /**
     * @param list<BulkSmsReceptor> $receptors
     *
     * @throws AdsefidValidationException if `receptors` is empty, a request-level required field is empty, or `message` exceeds 900 characters. Item errors are returned in the partial API response.
     */
    public function __construct(
        public readonly array $receptors,
        public readonly string $message,
        public readonly string $lineNumber,
        public readonly ?LineSelector $lineSelector = null,
        public readonly ?\DateTimeImmutable $sendTime = null,
    ) {
        LocalIdValidator::requireNonEmptyArray($this->receptors, 'receptors');
        LocalIdValidator::requireNonEmpty($this->message, 'message');
        LocalIdValidator::maxLength($this->message, self::MESSAGE_MAX_LENGTH, 'message');
        LocalIdValidator::requireNonEmpty($this->lineNumber, 'line_number');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'receptors' => array_map(static fn (BulkSmsReceptor $receptor): array => $receptor->toArray(), $this->receptors),
            'message' => $this->message,
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
