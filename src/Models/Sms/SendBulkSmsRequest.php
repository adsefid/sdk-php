<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;

final class SendBulkSmsRequest
{
    private const MESSAGE_MAX_LENGTH = 900;

    /**
     * @param array<int, array{receptor: string, local_id?: ?string, hide?: bool}> $receptors
     *
     * @throws AdsefidValidationException if `receptors` is empty, a required field is empty, `message` exceeds 900 characters, or any `receptors[].local_id` doesn't match the required pattern.
     */
    public function __construct(
        public readonly array $receptors,
        public readonly string $message,
        public readonly string $lineNumber,
        public readonly ?LineSelector $lineSelector = null,
        public readonly ?\DateTimeImmutable $sendTime = null,
    ) {
        LocalIdValidator::requireNonEmptyArray($this->receptors, 'receptors');
        LocalIdValidator::requireNonEmpty($this->message, 'message');
        LocalIdValidator::maxLength($this->message, self::MESSAGE_MAX_LENGTH, 'message');
        LocalIdValidator::requireNonEmpty($this->lineNumber, 'line_number');

        foreach ($this->receptors as $index => $receptor) {
            LocalIdValidator::requireNonEmpty($receptor['receptor'], sprintf('receptors[%d].receptor', $index));
            LocalIdValidator::validate($receptor['local_id'] ?? null, sprintf('receptors[%d].local_id', $index));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'receptors' => array_map(
                static fn (array $receptor): array => [
                    'receptor' => $receptor['receptor'],
                    'local_id' => $receptor['local_id'] ?? null,
                    'hide' => $receptor['hide'] ?? false,
                ],
                $this->receptors,
            ),
            'message' => $this->message,
            'line_number' => $this->lineNumber,
        ];

        if ($this->lineSelector !== null) {
            $data['line_selector'] = $this->lineSelector->value;
        }

        if ($this->sendTime !== null) {
            $data['send_time'] = $this->sendTime->format(DATE_ATOM);
        }

        return $data;
    }
}
