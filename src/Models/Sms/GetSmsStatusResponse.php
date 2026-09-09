<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Models\Common\StatusReceptor;

final class GetSmsStatusResponse
{
    /**
     * @param list<StatusReceptor> $receptors
     */
    public function __construct(
        public readonly array $receptors,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            receptors: array_map(
                static fn (array $receptor): StatusReceptor => StatusReceptor::fromArray($receptor),
                array_values((array) ($data['receptors'] ?? [])),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'receptors' => array_map(static fn (StatusReceptor $receptor): array => $receptor->toArray(), $this->receptors),
        ];
    }
}
