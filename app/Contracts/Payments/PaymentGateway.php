<?php

namespace App\Contracts\Payments;

use App\Data\Payments\CreateCheckoutData;
use App\Data\Payments\CreatedCheckoutData;
use App\Enums\PaymentProvider;

interface PaymentGateway
{
    public function provider(): PaymentProvider;

    public function createCheckout(CreateCheckoutData $data): CreatedCheckoutData;

    public function expireCheckout(string $checkoutId): void;
}
