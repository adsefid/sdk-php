<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\User;

use Adsefid\Sdk\Enums\TemplateState;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class GetUserTemplatesRequest
{
    private const DEFAULT_TAKE = 50;
    private const MIN_TAKE = 1;
    private const MAX_TAKE = 100;

    /**
     * @throws AdsefidValidationException if `skip` is negative or `take` is outside `1`-`100`.
     */
    public function __construct(
        public readonly ?TemplateState $state = null,
        public readonly int $skip = 0,
        public readonly int $take = self::DEFAULT_TAKE,
    ) {
        if ($this->skip < 0) {
            throw new AdsefidValidationException('"skip" must be non-negative.', 'skip');
        }

        LocalIdValidator::intRange($this->take, self::MIN_TAKE, self::MAX_TAKE, 'take');
    }

    /**
     * @return array<string, string|null>
     */
    public function toQuery(): array
    {
        return [
            'state' => $this->state?->value,
            'skip' => (string) $this->skip,
            'take' => (string) $this->take,
        ];
    }
}
