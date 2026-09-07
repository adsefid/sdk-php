<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class SendSingleSmsRequest
{
    private const MESSAGE_MAX_LENGTH = 900;

    /**
     * @throws AdsefidValidationException if a required field is empty, `message` exceeds 900 characters, or `localId` doesn't match the required pattern.
     */
    public function __construct(
        public readonly string $receptor,
        public readonly string $lineNumber,
        public readonly string $message,
        public readonly ?LineSelector $lineSelector = null,
        public readonly ?\DateTimeImmutable $sendTime = null,
        public readonly ?string $localId = null,
        public readonly bool $hide = false,
    ) {
        LocalIdValidator::requireNonEmpty($this->receptor, 'receptor');
        LocalIdValidator::requireNonEmpty($this->lineNumber, 'line_number');
        LocalIdValidator::requireNonEmpty($this->message, 'message');
        LocalIdValidator::maxLength($this->message, self::MESSAGE_MAX_LENGTH, 'message');
        LocalIdValidator::validate($this->localId, 'local_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'receptor' => $this->receptor,
            'line_number' => $this->lineNumber,
            'message' => $this->message,
            'hide' => $this->hide,
        ];

        if ($this->lineSelector !== null) {
            $data['line_selector'] = $this->lineSelector->value;
        }

        if ($this->sendTime !== null) {
            $data['send_time'] = $this->sendTime->format(DATE_ATOM);
        }

        if ($this->localId !== null) {
            $data['local_id'] = $this->localId;
        }

        return $data;
    }
}
