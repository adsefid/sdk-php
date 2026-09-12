<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

/**
 * One receptor-and-message entry of a P2P SMS send (`SendP2PSmsRequest::$messages`).
 *
 * Item validation happens server-side so valid entries can still be accepted
 * when another entry is rejected.
 */
final class P2PSmsMessage
{
    public function __construct(
        public readonly string $receptor,
        public readonly string $message,
        public readonly ?string $localId = null,
        public readonly bool $hide = false,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'receptor' => $this->receptor,
            'message' => $this->message,
            'hide' => $this->hide,
        ];

        if ($this->localId !== null) {
            $data['local_id'] = $this->localId;
        }

        return $data;
    }
}
