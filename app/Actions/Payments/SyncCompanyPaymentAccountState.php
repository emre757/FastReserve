<?php

namespace App\Actions\Payments;

use App\Contracts\Payments\PaymentAccountGateway;
use App\Enums\PaymentAccountStatus;
use App\Models\CompanyPaymentAccount;

final readonly class SyncCompanyPaymentAccountState
{
    public function __construct(
        private PaymentAccountGateway $gateway
    ) {}

    public function execute(CompanyPaymentAccount $paymentAccount): CompanyPaymentAccount
    {
        $accountData = $this->gateway->retrieveAccount(
            $paymentAccount->provider_account_id,
        );

        $isClosed = $accountData->status === PaymentAccountStatus::Closed;

        $paymentAccount->update([
            'status' => $accountData->status,
            'transfers_enabled' => $accountData->transfersEnabled,
            'closed_at' => $isClosed
                ? ($paymentAccount->closed_at ?? now())
                : null,
        ]);

        return $paymentAccount->refresh();
    }
}
