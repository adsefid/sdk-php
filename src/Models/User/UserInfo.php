<?php

declare(strict_types=1);

namespace Adsefid\Sdk\Models\User;

final class UserInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $companyName,
        public readonly int $creditLeft,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $accountStatus,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            companyName: (string) $data['company_name'],
            creditLeft: (int) $data['credit_left'],
            email: (string) $data['email'],
            phone: (string) $data['phone'],
            accountStatus: (string) $data['account_status'],
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
