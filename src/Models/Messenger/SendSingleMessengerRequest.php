<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class SendSingleMessengerRequest
{
    private const MESSAGE_MAX_LENGTH = 4000;

    /**
     * @throws AdsefidValidationException if a required field is empty, `message` exceeds 4000 characters, or `localId` doesn't match the required pattern.
     */
    public function __construct(
        public readonly string $message,
        public readonly string $receptor,
        public readonly string $profile,
        public readonly bool $hide = false,
        public readonly ?string $fileId = null,
        public readonly ?\DateTimeImmutable $sendTime = null,
        public readonly ?string $localId = null,
    ) {
        LocalIdValidator::requireNonEmpty($this->message, 'message');
        LocalIdValidator::maxLength($this->message, self::MESSAGE_MAX_LENGTH, 'message');
        LocalIdValidator::requireNonEmpty($this->receptor, 'receptor');
        LocalIdValidator::requireNonEmpty($this->profile, 'profile');
        LocalIdValidator::validate($this->localId, 'local_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'message' => $this->message,
            'receptor' => $this->receptor,
            'profile' => $this->profile,
            'hide' => $this->hide,
        ];

        if ($this->fileId !== null) {
            $data['file_id'] = $this->fileId;
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
