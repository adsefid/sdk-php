<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\User;

use Adsefid\Sdk\Enums\TemplateParameterType;
use Adsefid\Sdk\Enums\TemplateState;
use Adsefid\Sdk\Support\Wire;

final class UserTemplateItem
{
    /**
     * @param array<string, TemplateParameterType|string> $parameters
     */
    public function __construct(
        public readonly string $templateId,
        public readonly string $content,
        public readonly array $parameters,
        public readonly TemplateState|string $state,
        public readonly ?string $description,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $parameters = [];

        foreach ((array) ($data['parameters'] ?? []) as $name => $type) {
            $raw = (string) $type;
            $parameters[(string) $name] = TemplateParameterType::tryFrom($raw) ?? $raw;
        }

        return new self(
            templateId: Wire::string($data, 'template_id'),
            content: Wire::string($data, 'content'),
            parameters: $parameters,
            state: Wire::templateState($data, 'state'),
            description: isset($data['description']) ? Wire::string($data, 'description') : null,
            createdAt: Wire::date($data, 'created_at'),
            updatedAt: Wire::date($data, 'updated_at'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $parameters = [];

        foreach ($this->parameters as $name => $type) {
            $parameters[$name] = Wire::value($type);
        }

        return [
            'template_id' => $this->templateId,
            'content' => $this->content,
            'parameters' => $parameters,
            'state' => Wire::value($this->state),
            'description' => $this->description,
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'updated_at' => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}
