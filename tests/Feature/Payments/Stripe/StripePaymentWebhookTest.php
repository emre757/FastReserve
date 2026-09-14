<?php

use App\Enums\Currency;
use App\Enums\OfferingStatus;
use App\Enums\PaymentAccountStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Offering;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\Reservations\ReservationConfirmed;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Queue::fake();
    Notification::fake();

    $this->secret = 'whsec_testing';

    config([
        'services.stripe.payment_webhook_secret' => $this->secret,
    ]);

    $this->offering = Offering::factory()->create([
        'status' => OfferingStatus::Active,
        'price' => '10.50',
        'currency' => Currency::USD,
        'capacity' => 10,
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHour(),
        'booking_deadline_at' => now()->addDay(),
    ]);

    $this->paymentAccount = $this->offering->team
        ->companyPaymentAccounts()
        ->create([
            'provider' => PaymentProvider::Stripe,
            'provider_account_id' => 'acct_test',
            'status' => PaymentAccountStatus::Active,
            'transfers_enabled' => true,
        ]);

    $this->clientUser = User::factory()->create();

    $this->reservation = Reservation::factory()->for($this->offering, 'offering')->for($this->clientUser, 'user')->create([
        'quantity' => 1,
        'expired_at' => now()->addMinutes(10),
    ]);

    $this->payment = Payment::factory()->for($this->reservation, 'reservation')->for($this->paymentAccount, 'companyPaymentAccount')->create([
        'amount' => 1050,
        'currency' => 'USD',
    ]);

    $this->sendValidRequest = function ($payload) {
        $this->timestamp = time();

        $this->signature = hash_hmac(
            'sha256',
            $this->timestamp.'.'.$payload,
            $this->secret,
        );

        $this->call(
            method: 'POST',
            uri: route('webhooks.stripe.payments'),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => "t={$this->timestamp},v1={$this->signature}",
            ],
            content: $payload,
        )->assertNoContent();
    };
});

it('confirms a reservation from a signed successful checkout event', function () {
    $payload = json_encode([
        'id' => 'test_id',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => $this->payment->provider_checkout_id,
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    ($this->sendValidRequest)($payload);

    expect($this->payment->fresh()->status)
        ->toBe(PaymentStatus::Paid)
        ->and($this->payment->fresh()->provider_payment_id)
        ->toBe('pi_test')
        ->and($this->reservation->fresh()->status)
        ->toBe(ReservationStatus::Confirmed);

    Notification::assertSentToTimes(
        $this->clientUser,
        ReservationConfirmed::class
    );
});

it('confirms a reservation from async payment success', function () {
    $payload = json_encode([
        'id' => 'test_id',
        'object' => 'event',
        'type' => 'checkout.session.async_payment_succeeded',
        'data' => [
            'object' => [
                'id' => $this->payment->provider_checkout_id,
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    ($this->sendValidRequest)($payload);

    expect($this->payment->fresh()->status)
        ->toBe(PaymentStatus::Paid)
        ->and($this->payment->fresh()->provider_payment_id)
        ->toBe('pi_test')
        ->and($this->reservation->fresh()->status)
        ->toBe(ReservationStatus::Confirmed);

    Notification::assertSentToTimes(
        $this->clientUser,
        ReservationConfirmed::class
    );
});

it('fails a payment from async payment fail', function () {
    $payload = json_encode([
        'id' => 'test_id',
        'object' => 'event',
        'type' => 'checkout.session.async_payment_failed',
        'data' => [
            'object' => [
                'id' => $this->payment->provider_checkout_id,
                'object' => 'checkout.session',
                'payment_status' => 'unpaid',
                'payment_intent' => 'pi_test',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    ($this->sendValidRequest)($payload);

    expect($this->payment->fresh()->status)
        ->toBe(PaymentStatus::Failed)
        ->and($this->payment->fresh()->provider_payment_id)
        ->toBe('pi_test')
        ->and($this->reservation->fresh()->status)
        ->toBe(ReservationStatus::Pending);

    Notification::assertNothingSent();
});

it('expires a payment from checkout expired', function () {
    $payload = json_encode([
        'id' => 'test_id',
        'object' => 'event',
        'type' => 'checkout.session.expired',
        'data' => [
            'object' => [
                'id' => $this->payment->provider_checkout_id,
                'object' => 'checkout.session',
                'payment_status' => 'unpaid',
                'payment_intent' => 'pi_test',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    ($this->sendValidRequest)($payload);

    expect($this->payment->fresh()->status)
        ->toBe(PaymentStatus::Expired)
        ->and($this->payment->fresh()->provider_payment_id)
        ->toBe('pi_test')
        ->and($this->reservation->fresh()->status)
        ->toBe(ReservationStatus::Pending);

    Notification::assertNothingSent();
});

test('completed but unpaid event', function () {
    $payload = json_encode([
        'id' => 'test_id',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => $this->payment->provider_checkout_id,
                'object' => 'checkout.session',
                'payment_status' => 'unpaid',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    ($this->sendValidRequest)($payload);

    expect($this->payment->fresh()->status)
        ->toBe(PaymentStatus::Pending)
        ->and($this->payment->fresh()->provider_payment_id)
        ->toBeNull()
        ->and($this->reservation->fresh()->status)
        ->toBe(ReservationStatus::Pending);

    Notification::assertNothingSent();
});

it('rejects event from invalid signature', function () {
    $payload = json_encode([
        'id' => 'test_id',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => $this->payment->provider_checkout_id,
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $timestamp = time();

    $signature = hash_hmac(
        'sha256',
        $timestamp.'.'.$payload,
        'invalid_secret',
    );

    $this->call(
        method: 'POST',
        uri: route('webhooks.stripe.payments'),
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ],
        content: $payload,
    )->assertBadRequest();

    expect($this->payment->fresh()->status)
        ->toBe(PaymentStatus::Pending)
        ->and($this->payment->fresh()->provider_payment_id)
        ->toBeNull()
        ->and($this->reservation->fresh()->status)
        ->toBe(ReservationStatus::Pending);

    Notification::assertNothingSent();
});
