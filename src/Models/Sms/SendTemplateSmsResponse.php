<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\Sms;

use Adsefid\Sdk\Enums\LineSelector;
use Adsefid\Sdk\Enums\WebServiceMessageStatus;

final class SendTemplateSmsResponse
{
    /**
     * @param array<string, string|int|float> $parameters
     */
    public function __construct(
        public readonly string $groupId,
        public readonly string $messageId,
        public readonly WebServiceMessageStatus $status,
        public readonly ?string $localId,
        public readonly string $lineNumber,
        public readonly string $templateId,
        public readonly ?\DateTimeImmutable $sendTime,
        public readonly ?\DateTimeImmutable $expiryDate,
        public readonly ?LineSelector $lineSelector,
        public readonly float $cost,
        public readonly string $receptor,
        public readonly string $message,
        public readonly int $segmentCount,
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
            lineNumber: (string) $data['line_number'],
            templateId: (string) $data['template_id'],
            sendTime: isset($data['send_time']) ? new \DateTimeImmutable((string) $data['send_time']) : null,
            expiryDate: isset($data['expiry_date']) ? new \DateTimeImmutable((string) $data['expiry_date']) : null,
            lineSelector: isset($data['line_selector']) ? LineSelector::from((int) $data['line_selector']) : null,
            cost: (float) $data['cost'],
            receptor: (string) $data['receptor'],
            message: (string) $data['message'],
            segmentCount: (int) $data['segment_count'],
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
            'line_number' => $this->lineNumber,
            'template_id' => $this->templateId,
            'send_time' => $this->sendTime?->format(DATE_ATOM),
            'expiry_date' => $this->expiryDate?->format(DATE_ATOM),
            'line_selector' => $this->lineSelector?->value,
            'cost' => $this->cost,
            'receptor' => $this->receptor,
            'message' => $this->message,
            'segment_count' => $this->segmentCount,
            'parameters' => $this->parameters,
        ];
    }
}
