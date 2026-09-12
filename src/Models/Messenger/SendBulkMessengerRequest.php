<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class SendBulkMessengerRequest
{
    private const MESSAGE_MAX_LENGTH = 4000;

    /**
     * @param list<BulkMessengerReceptor> $receptors
     *
     * @throws AdsefidValidationException if `receptors` is empty, a request-level required field is empty, or `message` exceeds 4000 characters. Item errors are returned in the partial API response.
     */
    public function __construct(
        public readonly array $receptors,
        public readonly string $message,
        public readonly string $profile,
        public readonly ?string $fileId = null,
        public readonly ?\DateTimeImmutable $sendTime = null,
    ) {
        LocalIdValidator::requireNonEmptyArray($this->receptors, 'receptors');
        LocalIdValidator::requireNonEmpty($this->message, 'message');
        LocalIdValidator::maxLength($this->message, self::MESSAGE_MAX_LENGTH, 'message');
        LocalIdValidator::requireNonEmpty($this->profile, 'profile');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'receptors' => array_map(static fn (BulkMessengerReceptor $receptor): array => $receptor->toArray(), $this->receptors),
            'message' => $this->message,
            'profile' => $this->profile,
        ];

        if ($this->fileId !== null) {
            $data['file_id'] = $this->fileId;
        }

        if ($this->sendTime !== null) {
            $data['send_time'] = $this->sendTime->format(DATE_ATOM);
        }

        return $data;
    }
}
