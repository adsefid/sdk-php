<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

final class UploadMessengerFileResponse
{
    public function __construct(
        public readonly string $fileId,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fileId: (string) $data['file_id'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file_id' => $this->fileId,
        ];
    }
}
