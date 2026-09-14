<?php

use App\Data\Payments\CreateCheckoutData;
use App\Enums\Currency;
use App\Payments\Money;
use App\Payments\Stripe\StripePaymentGateway;
use Illuminate\Support\Arr;
use Stripe\Checkout\Session;
use Stripe\Exception\InvalidRequestException;
use Stripe\Service\Checkout\SessionService;
use Stripe\StripeClient;

beforeEach(function () {
    $this->sessions = Mockery::mock(SessionService::class);
    $stripe = Mockery::mock(StripeClient::class);

    $checkout = (object) [
        'sessions' => $this->sessions,
    ];

    $stripe->shouldReceive('getService')
        ->with('checkout')
        ->andReturn($checkout);

    $this->checkoutUrl = 'https://checkout.example.test/session';
    $this->gateway = new StripePaymentGateway($stripe);
});

describe('createCheckout', function () {
    it('creates a Stripe checkout with the correct payment details', function () {
        $this->sessions->shouldReceive('create')
            ->once()
            ->withArgs(function (array $parameters, array $options): bool {
                expect($parameters['mode'])->toBe('payment')
                    ->and($parameters['line_items'])->toBe([
                        [
                            'price_data' => [
                                'currency' => 'USD',
                                'unit_amount' => 1050,
                                'product_data' => [
                                    'name' => 'Cooking class',
                                ],
                            ],
                            'quantity' => 2,
                        ],
                    ])
                    ->and($parameters['payment_intent_data']['application_fee_amount'])
                    ->toBe(210)
                    ->and($parameters['payment_intent_data']['transfer_data'])
                    ->toBe(['destination' => 'acct_test'])
                    ->and($parameters['payment_intent_data']['metadata'])->toBe([
                        'reservation_id' => '20',
                        'offering_id' => '30',
                        'team_id' => '40',
                        'payment_id' => '10',
                    ])
                    ->and($parameters['client_reference_id'])->toBe('20')
                    ->and($parameters['success_url'])
                    ->toBe(route('reservations.payment.processing', 20))
                    ->and($parameters['cancel_url'])
                    ->toBe(route('reservations.show', 20))
                    ->and($options)->toBe([
                        'idempotency_key' => 'reservation-checkout-10',
                    ]);

                return true;
            })
            ->andReturn(Session::constructFrom([
                'id' => 'cs_test',
                'url' => $this->checkoutUrl,
            ]));

        $result = $this->gateway->createCheckout(new CreateCheckoutData(
            paymentId: 10,
            unitPrice: Money::fromDecimal('10.50', Currency::USD),
            quantity: 2,
            offerName: 'Cooking class',
            reservationId: 20,
            offeringId: 30,
            teamId: 40,
            connectedAccountId: 'acct_test',
        ));

        expect($result->id)->toBe('cs_test')
            ->and($result->url)->toBe($this->checkoutUrl);
    });

    test('platform fee', function () {
        $this->sessions->shouldReceive('create')
            ->once()
            ->withArgs(function (array $parameters, array $options): bool {
                expect($parameters['payment_intent_data']['application_fee_amount'])
                    ->toBe(211);

                return true;
            })
            ->andReturn(Session::constructFrom([
                'id' => 'cs_test',
                'url' => $this->checkoutUrl,
            ]));

        $this->gateway->createCheckout(new CreateCheckoutData(
            paymentId: 10,
            unitPrice: Money::fromDecimal('10.59', Currency::USD),
            quantity: 2,
            offerName: 'Cooking class',
            reservationId: 20,
            offeringId: 30,
            teamId: 40,
            connectedAccountId: 'acct_test',
        ));

    });

    it('reuses the idempotency key for the same payment', function () {
        $this->sessions->shouldReceive('create')
            ->twice()
            ->withArgs(function (array $parameters, array $options): bool {
                expect($options['idempotency_key'])
                    ->toBe('reservation-checkout-10');

                return true;
            })
            ->andReturn(Session::constructFrom([
                'id' => 'cs_test',
                'url' => $this->checkoutUrl,
            ]));

        $data = new CreateCheckoutData(
            paymentId: 10,
            unitPrice: Money::fromDecimal('10.59', Currency::USD),
            quantity: 2,
            offerName: 'Cooking class',
            reservationId: 20,
            offeringId: 30,
            teamId: 40,
            connectedAccountId: 'acct_test',
        );

        $this->gateway->createCheckout($data);
        $this->gateway->createCheckout($data);
    });

    it('uses a different idempotency key for a new payment', function () {
        $this->sessions->shouldReceive('create')
            ->once()
            ->withArgs(function (array $parameters, array $options): bool {
                expect($options['idempotency_key'])
                    ->toBe('reservation-checkout-10');

                return true;
            })
            ->andReturn(Session::constructFrom([
                'id' => 'cs_test',
                'url' => $this->checkoutUrl,
            ]));

        $data = new CreateCheckoutData(
            paymentId: 10,
            unitPrice: Money::fromDecimal('10.59', Currency::USD),
            quantity: 2,
            offerName: 'Cooking class',
            reservationId: 20,
            offeringId: 30,
            teamId: 40,
            connectedAccountId: 'acct_test',
        );

        $this->gateway->createCheckout($data);

        $modified = new CreateCheckoutData(...[
            ...Arr::except(get_object_vars($data), 'paymentId'),
            'paymentId' => 12,
        ]);

        $this->sessions->shouldReceive('create')
            ->once()
            ->withArgs(function (array $parameters, array $options): bool {
                expect($options['idempotency_key'])
                    ->toBe('reservation-checkout-12');

                return true;
            })
            ->andReturn(Session::constructFrom([
                'id' => 'cs_test',
                'url' => $this->checkoutUrl,
            ]));

        $this->gateway->createCheckout($modified);
    });
});

describe('expireCheckout', function () {
    beforeEach(function () {
        $this->sessions->shouldReceive('create')
            ->once()
            ->withArgs(function (array $parameters, array $options): bool {
                expect($options['idempotency_key'])
                    ->toBe('reservation-checkout-10');

                return true;
            })
            ->andReturn(Session::constructFrom([
                'id' => 'cs_test',
                'url' => $this->checkoutUrl,
            ]));

        $this->checkout = $this->gateway->createCheckout(new CreateCheckoutData(
            paymentId: 10,
            unitPrice: Money::fromDecimal('10.59', Currency::USD),
            quantity: 2,
            offerName: 'Cooking class',
            reservationId: 20,
            offeringId: 30,
            teamId: 40,
            connectedAccountId: 'acct_test',
        ));
    });

    it('expires an open checkout', function () {
        $this->sessions->shouldReceive('expire')
            ->once()
            ->with($this->checkout->id)
            ->andReturn(Session::constructFrom([
                'id' => $this->checkout->id,
                'status' => 'expired',
            ]));

        $this->gateway->expireCheckout($this->checkout->id);
    });

    it('accepts an already expired checkout', function () {
        $checkoutId = $this->checkout->id;

        $this->sessions->shouldReceive('expire')
            ->once()
            ->with($checkoutId)
            ->andThrow(
                InvalidRequestException::factory(
                    'Checkout session cannot be expired.'
                ),
            );

        $this->sessions->shouldReceive('retrieve')
            ->once()
            ->with($checkoutId)
            ->andReturn(Session::constructFrom([
                'id' => $checkoutId,
                'status' => 'expired',
            ]));

        $this->gateway->expireCheckout($checkoutId);
    });

    it('rethrows the error when checkout is not expired', function () {
        $checkoutId = $this->checkout->id;

        $this->sessions->shouldReceive('expire')
            ->once()
            ->with($checkoutId)
            ->andThrow(
                InvalidRequestException::factory(
                    'Checkout session cannot be expired.'
                ),
            );

        $this->sessions->shouldReceive('retrieve')
            ->once()
            ->with($checkoutId)
            ->andReturn(Session::constructFrom([
                'id' => $checkoutId,
                'status' => 'complete',
            ]));

        expect(fn () => $this->gateway->expireCheckout($checkoutId))->toThrow(InvalidRequestException::class);
    });
});
