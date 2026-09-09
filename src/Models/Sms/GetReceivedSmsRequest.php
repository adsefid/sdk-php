<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class GetReceivedSmsRequest
{
    private const MIN_COUNT = 1;
    private const MAX_COUNT = 499;

    /**
     * @throws AdsefidValidationException if `lineNumber` is empty or `count` is outside `1`-`499`.
     */
    public function __construct(
        public readonly string $lineNumber,
        public readonly ?int $count = null,
        public readonly ?\DateTimeImmutable $since = null,
    ) {
        LocalIdValidator::requireNonEmpty($this->lineNumber, 'line_number');

        if ($this->count !== null) {
            LocalIdValidator::intRange($this->count, self::MIN_COUNT, self::MAX_COUNT, 'count');
        }
    }

    /**
     * @return array<string, string|null>
     */
    public function toQuery(): array
    {
        return [
            'line_number' => $this->lineNumber,
            'count' => $this->count !== null ? (string) $this->count : null,
            'since' => $this->since?->format(DATE_ATOM),
        ];
    }
}
