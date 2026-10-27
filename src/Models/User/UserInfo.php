<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\User;

use Adsefid\Sdk\Support\Wire;

final class UserInfo
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $companyName,
        public readonly float $creditLeft,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly string $accountStatus,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Wire::string($data, 'name'),
            companyName: isset($data['company_name']) ? Wire::string($data, 'company_name') : null,
            creditLeft: Wire::float($data, 'credit_left'),
            email: isset($data['email']) ? Wire::string($data, 'email') : null,
            phone: isset($data['phone']) ? Wire::string($data, 'phone') : null,
            accountStatus: Wire::string($data, 'account_status'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'company_name' => $this->companyName,
            'credit_left' => $this->creditLeft,
            'email' => $this->email,
            'phone' => $this->phone,
            'account_status' => $this->accountStatus,
        ];
    }
}
