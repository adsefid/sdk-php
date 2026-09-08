<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;
use Adsefid\Sdk\Support\TemplateParameters;

final class SendTemplateSmsRequest
{
    /**
     * @param array<string, string|int|float> $parameters A number-typed parameter may be sent as a numeric string to keep its exact digits (see `TemplateParameters`).
     *
     * @throws AdsefidValidationException if `templateId`, `receptor`, or `lineNumber` is empty, or `localId` doesn't match the required pattern.
     */
    public function __construct(
        public readonly string $templateId,
        public readonly array $parameters,
        public readonly string $receptor,
        public readonly string $lineNumber,
        public readonly ?string $localId = null,
        public readonly ?LineSelector $lineSelector = null,
        public readonly ?\DateTimeImmutable $expiryDate = null,
    ) {
        LocalIdValidator::requireNonEmpty($this->templateId, 'template_id');
        LocalIdValidator::requireNonEmpty($this->receptor, 'receptor');
        LocalIdValidator::requireNonEmpty($this->lineNumber, 'line_number');
        LocalIdValidator::validate($this->localId, 'local_id');
        TemplateParameters::validate($this->parameters);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'template_id' => $this->templateId,
            'parameters' => $this->parameters,
            'receptor' => $this->receptor,
            'line_number' => $this->lineNumber,
        ];

        if ($this->localId !== null) {
            $data['local_id'] = $this->localId;
        }

        if ($this->lineSelector !== null) {
            $data['line_selector'] = $this->lineSelector->value;
        }

        if ($this->expiryDate !== null) {
            $data['expiry_date'] = $this->expiryDate->format(DATE_ATOM);
        }

        return $data;
    }
}
