<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class SendP2PMessengerRequest
{
    private const MESSAGE_MAX_LENGTH = 4000;

    /**
     * @param list<P2PMessengerReceptor> $receptors
     *
     * @throws AdsefidValidationException if `receptors` is empty, a required field is empty, any `receptors[].message` exceeds 4000 characters, or any `receptors[].local_id` doesn't match the required pattern.
     */
    public function __construct(
        public readonly array $receptors,
        public readonly string $profile,
        public readonly ?string $fileId = null,
        public readonly ?\DateTimeImmutable $sendTime = null,
    ) {
        LocalIdValidator::requireNonEmptyArray($this->receptors, 'receptors');
        LocalIdValidator::requireNonEmpty($this->profile, 'profile');

        foreach ($this->receptors as $index => $receptor) {
            LocalIdValidator::requireNonEmpty($receptor->receptor, sprintf('receptors[%d].receptor', $index));
            $body = LocalIdValidator::requireNonEmpty($receptor->message, sprintf('receptors[%d].message', $index));
            LocalIdValidator::maxLength($body, self::MESSAGE_MAX_LENGTH, sprintf('receptors[%d].message', $index));
            LocalIdValidator::validate($receptor->localId, sprintf('receptors[%d].local_id', $index));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'receptors' => array_map(static fn (P2PMessengerReceptor $receptor): array => $receptor->toArray(), $this->receptors),
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
