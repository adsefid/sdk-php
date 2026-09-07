<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\User;

use Adsefid\Sdk\Enums\TemplateParameterType;
use Adsefid\Sdk\Enums\TemplateState;

final class UserTemplateItem
{
    /**
     * @param array<string, TemplateParameterType> $parameters
     */
    public function __construct(
        public readonly string $templateId,
        public readonly string $content,
        public readonly array $parameters,
        public readonly TemplateState $state,
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
            $parameterType = TemplateParameterType::tryFrom((string) $type);

            if ($parameterType !== null) {
                $parameters[(string) $name] = $parameterType;
            }
        }

        return new self(
            templateId: (string) $data['template_id'],
            content: (string) $data['content'],
            parameters: $parameters,
            state: TemplateState::from((string) $data['state']),
            description: isset($data['description']) ? (string) $data['description'] : null,
            createdAt: new \DateTimeImmutable((string) $data['created_at']),
            updatedAt: new \DateTimeImmutable((string) $data['updated_at']),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $parameters = [];

        foreach ($this->parameters as $name => $type) {
            $parameters[$name] = $type->value;
        }

        return [
            'template_id' => $this->templateId,
            'content' => $this->content,
            'parameters' => $parameters,
            'state' => $this->state->value,
            'description' => $this->description,
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'updated_at' => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}
