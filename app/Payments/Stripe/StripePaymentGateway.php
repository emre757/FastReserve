<?php

namespace App\Payments\Stripe;

use App\Contracts\Payments\PaymentGateway;
use App\Data\Payments\CreateCheckoutData;
use App\Data\Payments\CreatedCheckoutData;
use App\Enums\PaymentProvider;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;

final readonly class StripePaymentGateway implements PaymentGateway
{
    public function __construct(
        private StripeClient $stripe
    ) {}

    public function provider(): PaymentProvider
    {
        return PaymentProvider::Stripe;
    }

    public function createCheckout(CreateCheckoutData $data): CreatedCheckoutData
    {
        $totalPrice = $data->unitPrice->multiply($data->quantity)->minorUnits;
        $platformFee = (int) ($totalPrice / 10); // just remove the decimals, no rounding

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',

            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $data->unitPrice->currency->value,
                        'unit_amount' => $data->unitPrice->minorUnits,
                        'product_data' => [
                            'name' => $data->offerName,
                        ],
                    ],
                    'quantity' => $data->quantity,
                ],
            ],

            'client_reference_id' => (string) $data->reservationId,

            'payment_intent_data' => [
                'application_fee_amount' => $platformFee,

                'transfer_data' => [
                    'destination' => $data->connectedAccountId,
                ],

                'metadata' => [
                    'reservation_id' => (string) $data->reservationId,
                    'offering_id' => (string) $data->offeringId,
                    'team_id' => (string) $data->teamId,
                    'payment_id' => (string) $data->paymentId,
                ],
            ],

            'success_url' => route('reservations.payment.processing', $data->reservationId),
            'cancel_url' => route('reservations.show', $data->reservationId),
        ], [
            'idempotency_key' => "reservation-checkout-{$data->paymentId}", // allow multiple checkout sessions per reservation
        ]);

        return new CreatedCheckoutData(
            $session->id,
            $session->url,
        );
    }

    /**
     * @throws ApiErrorException
     * @throws InvalidRequestException
     */
    // always assume expire succeeded unless stripe throws an error without checkout status changing to expired
    // error is expected when trying to expire a non-expirable checkout which is why we catch and suppress this
    public function expireCheckout(string $checkoutId): void
    {
        try {
            $this->stripe->checkout->sessions->expire($checkoutId);
        } catch (InvalidRequestException $exception) {
            $session = $this->stripe->checkout->sessions->retrieve($checkoutId);

            if ($session->status === 'expired') {
                return;
            }

            throw $exception;
        }
    }
}
