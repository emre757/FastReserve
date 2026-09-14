<?php

namespace App\Data\Payments;

final readonly class CreatePaymentAccountData
{
    public function __construct(
        public string $email,
        public string $companyName,
        public string $country,
        public int $companyId,
    ) {}
}
