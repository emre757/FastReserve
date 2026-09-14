<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payments\SyncCompanyPaymentAccountState;
use App\Contracts\Payments\PaymentAccountGateway;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

final class CompanyPaymentAccountController
{
    public function delete(Team $team, PaymentAccountGateway $gateway, SyncCompanyPaymentAccountState $syncAccountState): RedirectResponse
    {
        $paymentAccount = $team->companyPaymentAccounts()
            ->forProvider($gateway->provider())
            ->whereNull('closed_at')
            ->firstOrFail();

        $gateway->closeAccount(
            $paymentAccount->provider_account_id,
        );

        $syncAccountState->execute($paymentAccount);

        return back()->with(
            'success',
            'Payment account disconnected.',
        );
    }
}
