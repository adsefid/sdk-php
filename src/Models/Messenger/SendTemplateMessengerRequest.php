<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Exceptions\AdsefidValidationException;
use Adsefid\Sdk\Support\LocalIdValidator;
use Adsefid\Sdk\Support\TemplateParameters;

final class SendTemplateMessengerRequest
{
    /**
     * @param array<string, string|int|float> $parameters A number-typed parameter may be sent as a numeric string to keep its exact digits (see `TemplateParameters`).
     *
     * @throws AdsefidValidationException if `templateId`, `receptor`, or `profile` is empty, or `localId` doesn't match the required pattern.
     */
    public function __construct(
        public readonly string $templateId,
        public readonly array $parameters,
        public readonly string $receptor,
        public readonly string $profile,
        public readonly ?string $localId = null,
        public readonly ?\DateTimeImmutable $expiryDate = null,
    ) {
        LocalIdValidator::requireNonEmpty($this->templateId, 'template_id');
        LocalIdValidator::requireNonEmpty($this->receptor, 'receptor');
        LocalIdValidator::requireNonEmpty($this->profile, 'profile');
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
            'profile' => $this->profile,
        ];

        if ($this->localId !== null) {
            $data['local_id'] = $this->localId;
        }

        if ($this->expiryDate !== null) {
            $data['expiry_date'] = $this->expiryDate->format(DATE_ATOM);
        }

        return $data;
    }
}
