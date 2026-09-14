<?php

namespace App\Http\Controllers\Payments;

use App\Contracts\Payments\PaymentAccountGateway;
use App\Data\Payments\CreatePaymentAccountData;
use App\Enums\PaymentAccountStatus;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class OnboardingController extends Controller
{
    public function returned(Team $team, PaymentAccountGateway $gateway): RedirectResponse
    {
        $provider = $gateway->provider()->value;

        $paymentAccount = $team->companyPaymentAccounts()
            ->where('provider', $provider)
            ->firstOrFail();

        $accountData = $gateway->retrieveAccount(
            $paymentAccount->provider_account_id,
        );

        $paymentAccount->update([
            'status' => $accountData->status,
            'transfers_enabled' => $accountData->transfersEnabled,
        ]);

        return to_route('teams.edit', $team)
            ->with('success', "$provider account status updated.");
    }

    public function refresh(Team $team, PaymentAccountGateway $gateway): Response
    {
        $provider = $gateway->provider()->value;

        $paymentAccount = $team->companyPaymentAccounts()
            ->where('provider', $provider)
            ->firstOrFail();

        $onboardingUrl = $gateway->createOnboardingLink(
            providerAccountId: $paymentAccount->provider_account_id,
            returnUrl: route(
                'companies.payment-account.return',
                ['team' => $team],
            ),
            refreshUrl: route(
                'companies.payment-account.refresh',
                ['team' => $team],
            ),
        );

        return Inertia::location($onboardingUrl);
    }

    // TODO: idempotency protection needed
    public function store(Team $team, PaymentAccountGateway $gateway): Response|RedirectResponse
    {
        $user = auth()->user();

        $provider = $gateway->provider()->value;

        $paymentAccount = $team->companyPaymentAccounts()
            ->where('provider', $provider)
            ->first();

        if ($paymentAccount === null) {
            $createdAccount = $gateway->createAccount(
                new CreatePaymentAccountData(
                    email: $user->email,
                    companyName: $team->name,
                    country: 'NL',
                    companyId: $team->id,
                )
            );

            $paymentAccount = $team->companyPaymentAccounts()->create([
                'provider' => $provider,
                'provider_account_id' => $createdAccount->providerAccountId,
                'status' => $createdAccount->status,
                'transfers_enabled' => $createdAccount->transfersEnabled,
            ]);
        }

        $statusResponse = match ($paymentAccount->status) {
            PaymentAccountStatus::Pending => null,

            PaymentAccountStatus::Active => to_route('dashboard')
                ->with('info', "$provider is already connected."),

            PaymentAccountStatus::Closed => to_route('dashboard')
                ->with('error', "This $provider account is closed."),
        };

        if ($statusResponse !== null) {
            return $statusResponse;
        }

        $onboardingLink = $gateway->createOnboardingLink(
            providerAccountId: $paymentAccount->provider_account_id,
            returnUrl: route(
                'companies.payment-account.return',
                ['team' => $team],
            ),
            refreshUrl: route(
                'companies.payment-account.refresh',
                ['team' => $team],
            ),
        );

        return Inertia::location($onboardingLink);
    }
}
