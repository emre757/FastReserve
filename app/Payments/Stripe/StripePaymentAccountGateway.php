<?php

namespace App\Payments\Stripe;

use App\Contracts\Payments\PaymentAccountGateway;
use App\Data\Payments\CreatePaymentAccountData;
use App\Data\Payments\PaymentAccountData;
use App\Enums\PaymentAccountStatus;
use App\Enums\PaymentProvider;
use RuntimeException;
use Stripe\StripeClient;

final readonly class StripePaymentAccountGateway implements PaymentAccountGateway
{
    public function __construct(private StripeClient $stripe) {}

    public function provider(): PaymentProvider
    {
        return PaymentProvider::Stripe;
    }

    public function createAccount(
        CreatePaymentAccountData $data,
    ): PaymentAccountData {
        $account = $this->stripe->v2->core->accounts->create([
            'contact_email' => $data->email,
            'display_name' => $data->companyName,

            'defaults' => [
                'responsibilities' => [
                    'fees_collector' => 'application',
                    'losses_collector' => 'application',
                ],
            ],

            'dashboard' => 'express',

            'identity' => [
                'country' => strtolower($data->country),
            ],

            'configuration' => [
                'recipient' => [
                    'capabilities' => [
                        'stripe_balance' => [
                            'stripe_transfers' => [
                                'requested' => true,
                            ],
                        ],
                    ],
                ],
            ],

            'metadata' => [
                'company_id' => (string) $data->companyId,
            ],

            'include' => [
                'configuration.recipient',
                'identity',
                'requirements',
            ],
        ]);

        return new PaymentAccountData(
            providerAccountId: $account->id,
            status: PaymentAccountStatus::Pending,
            transfersEnabled: false
        );
    }

    public function createOnboardingLink(
        string $providerAccountId,
        string $returnUrl,
        string $refreshUrl,
    ): string {
        $accountLink = $this->stripe->v2->core->accountLinks->create([
            'account' => $providerAccountId,

            'use_case' => [
                'type' => 'account_onboarding',

                'account_onboarding' => [
                    'configurations' => ['recipient'],
                    'return_url' => $returnUrl,
                    'refresh_url' => $refreshUrl,
                    'collection_options' => [
                        'fields' => 'eventually_due',
                        'future_requirements' => 'include',
                    ],
                ],
            ],
        ]);

        return $accountLink->url;
    }

    public function retrieveAccount(
        string $providerAccountId,
    ): PaymentAccountData {
        $account = $this->stripe->v2->core->accounts->retrieve(
            $providerAccountId,
            [
                'include' => [
                    'configuration.recipient',
                    'requirements',
                ],
            ],
        );

        $accountData = $account->toArray();

        $transfersEnabled = (
            $accountData['configuration']['recipient']['capabilities']['stripe_balance']['stripe_transfers']['status'] ?? null
        ) === 'active';

        $status = match (true) {
            $account->closed === true => PaymentAccountStatus::Closed,
            $transfersEnabled => PaymentAccountStatus::Active,
            default => PaymentAccountStatus::Pending,
        };

        return new PaymentAccountData(
            providerAccountId: $account->id,
            status: $status,
            transfersEnabled: $transfersEnabled,
        );
    }

    public function closeAccount(
        string $providerAccountId,
    ): void {
        $account = $this->stripe->v2->core->accounts->close(
            $providerAccountId,
            [
                'applied_configurations' => ['recipient'],
            ],
        );

        if ($account->closed !== true) {
            throw new RuntimeException(
                "Stripe did not confirm that account {$providerAccountId} was closed."
            );
        }
    }
}
