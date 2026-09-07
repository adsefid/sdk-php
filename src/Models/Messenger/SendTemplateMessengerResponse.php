<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Messenger;

use Adsefid\Sdk\Enums\WebServiceMessageStatus;

final class SendTemplateMessengerResponse
{
    /**
     * @param array<string, string|int|float> $parameters
     */
    public function __construct(
        public readonly string $groupId,
        public readonly string $messageId,
        public readonly WebServiceMessageStatus $status,
        public readonly ?string $localId,
        public readonly string $templateId,
        public readonly ?\DateTimeImmutable $sendTime,
        public readonly ?\DateTimeImmutable $expiryDate,
        public readonly int $cost,
        public readonly string $receptor,
        public readonly string $message,
        public readonly string $profile,
        public readonly string $messenger,
        public readonly array $parameters,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            groupId: (string) $data['group_id'],
            messageId: (string) $data['message_id'],
            status: WebServiceMessageStatus::from((int) $data['status']),
            localId: isset($data['local_id']) ? (string) $data['local_id'] : null,
            templateId: (string) $data['template_id'],
            sendTime: isset($data['send_time']) ? new \DateTimeImmutable((string) $data['send_time']) : null,
            expiryDate: isset($data['expiry_date']) ? new \DateTimeImmutable((string) $data['expiry_date']) : null,
            cost: (int) $data['cost'],
            receptor: (string) $data['receptor'],
            message: (string) $data['message'],
            profile: (string) $data['profile'],
            messenger: (string) $data['messenger'],
            parameters: (array) ($data['parameters'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'group_id' => $this->groupId,
            'message_id' => $this->messageId,
            'status' => $this->status->value,
            'local_id' => $this->localId,
            'template_id' => $this->templateId,
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'expiry_date' => $this->expiryDate?->format(DATE_ATOM),
            'cost' => $this->cost,
            'receptor' => $this->receptor,
            'message' => $this->message,
            'profile' => $this->profile,
            'messenger' => $this->messenger,
            'parameters' => $this->parameters,
        ];
    }
}
