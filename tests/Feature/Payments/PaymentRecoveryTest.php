<?php

use App\Contracts\Payments\PaymentGateway;
use App\Data\Payments\CreateCheckoutData;
use App\Data\Payments\CreatedCheckoutData;
use App\Enums\Currency;
use App\Enums\OfferingStatus;
use App\Enums\PaymentAccountStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Offering;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(DatabaseMigrations::class);

it('preserves the payment after checkout creation fails and reuses it on retry', function () {
    Queue::fake();
    Notification::fake();
    Exceptions::fake([RuntimeException::class]);

    $user = User::factory()->create();

    $offering = Offering::factory()->create([
        'status' => OfferingStatus::Active,
        'price' => '10.50',
        'currency' => Currency::USD,
        'capacity' => 10,
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHour(),
        'booking_deadline_at' => now()->addDay(),
    ]);

    $paymentAccount = $offering->team->companyPaymentAccounts()->create([
        'provider' => PaymentProvider::Stripe,
        'provider_account_id' => 'acct_test',
        'status' => PaymentAccountStatus::Active,
        'transfers_enabled' => true,
    ]);

    $reservation = Reservation::factory()
        ->for($offering, 'offering')
        ->for($user, 'user')
        ->create([
            'quantity' => 2,
            'status' => ReservationStatus::Pending,
            'expired_at' => now()->addMinutes(10),
        ]);

    $reservationSnapshot = $reservation->fresh()->getAttributes();
    $exception = new RuntimeException('Payment provider connection failed.');
    $firstCheckoutData = null;
    $gateway = $this->mock(PaymentGateway::class);

    $gateway->shouldReceive('provider')
        ->twice()
        ->andReturn(PaymentProvider::Stripe);

    $gateway->shouldNotReceive('expireCheckout');

    $gateway->shouldReceive('createCheckout')
        ->once()
        ->andReturnUsing(function (CreateCheckoutData $data) use (&$firstCheckoutData, $exception): never {
            $firstCheckoutData = $data;

            throw $exception;
        });

    $this->actingAs($user)
        ->postJson(route('reservations.payment.checkout', $reservation))
        ->assertServerError();

    Exceptions::assertReported(fn (RuntimeException $reported): bool => $reported === $exception);

    $payment = $reservation->payments()->sole();

    expect($payment->id)->toBe($firstCheckoutData->paymentId)
        ->and($payment->status)->toBe(PaymentStatus::Creating)
        ->and($payment->provider_checkout_id)->toBeNull()
        ->and($payment->provider_payment_id)->toBeNull()
        ->and($payment->paid_at)->toBeNull()
        ->and($payment->amount)->toBe(2100)
        ->and($payment->currency)->toBe('USD')
        ->and($payment->company_payment_account_id)->toBe($paymentAccount->id)
        ->and($reservation->fresh()->getAttributes())->toBe($reservationSnapshot);

    Notification::assertNothingSent();

    $checkoutUrl = 'https://checkout.example.test/recovered';

    $gateway->shouldReceive('createCheckout')
        ->once()
        ->withArgs(function (CreateCheckoutData $data) use ($firstCheckoutData): bool {
            expect($data)->toEqual($firstCheckoutData);

            return true;
        })
        ->andReturn(new CreatedCheckoutData(
            id: 'cs_recovered',
            url: $checkoutUrl,
        ));

    $this->post(route('reservations.payment.checkout', $reservation))
        ->assertRedirect($checkoutUrl);

    $retriedPayment = $reservation->payments()->sole();

    expect($retriedPayment->id)->toBe($payment->id)
        ->and($retriedPayment->status)->toBe(PaymentStatus::Pending)
        ->and($retriedPayment->provider_checkout_id)->toBe('cs_recovered')
        ->and($retriedPayment->provider_payment_id)->toBeNull()
        ->and($retriedPayment->paid_at)->toBeNull()
        ->and($retriedPayment->amount)->toBe($payment->amount)
        ->and($retriedPayment->currency)->toBe($payment->currency)
        ->and($retriedPayment->company_payment_account_id)->toBe($paymentAccount->id)
        ->and($reservation->fresh()->getAttributes())->toBe($reservationSnapshot);

    Notification::assertNothingSent();
});
