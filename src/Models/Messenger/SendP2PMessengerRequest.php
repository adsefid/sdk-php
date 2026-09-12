<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class SendP2PMessengerRequest
{
    /**
     * @param list<P2PMessengerReceptor> $receptors
     *
     * @throws AdsefidValidationException if `receptors` is empty or `profile` is empty. Item errors are returned in the partial API response.
     */
    public function __construct(
        public readonly array $receptors,
        public readonly string $profile,
        public readonly ?string $fileId = null,
        public readonly ?\DateTimeImmutable $sendTime = null,
    ) {
        LocalIdValidator::requireNonEmptyArray($this->receptors, 'receptors');
        LocalIdValidator::requireNonEmpty($this->profile, 'profile');
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
