<?php

namespace App\Contracts\Payments;

use App\Data\Payments\CreatePaymentAccountData;
use App\Data\Payments\PaymentAccountData;
use App\Enums\PaymentProvider;

interface PaymentAccountGateway
{
    public function provider(): PaymentProvider;

    public function createAccount(
        CreatePaymentAccountData $data,
    ): PaymentAccountData;

    public function createOnboardingLink(
        string $providerAccountId,
        string $returnUrl,
        string $refreshUrl,
    ): string;

    public function retrieveAccount(
        string $providerAccountId,
    ): PaymentAccountData;

    public function closeAccount(
        string $providerAccountId,
    ): void;
}
