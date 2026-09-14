<?php

namespace App\Data\Payments;

use App\Payments\Money;

final readonly class CreateCheckoutData
{
    public function __construct(
        public int $paymentId,
        public Money $unitPrice,
        public int $quantity,
        public string $offerName,
        public int $reservationId,
        public int $offeringId,
        public int $teamId,
        public string $connectedAccountId,
    ) {}
}
