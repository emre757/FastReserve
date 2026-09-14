<?php

namespace App\Data\Payments;

use App\Enums\PaymentAccountStatus;

final readonly class PaymentAccountData
{
    public function __construct(
        public string $providerAccountId,
        public PaymentAccountStatus $status,
        public bool $transfersEnabled,
    ) {}
}
