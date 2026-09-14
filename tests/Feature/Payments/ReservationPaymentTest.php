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
use Illuminate\Support\Facades\Queue;

/** this test is only responsible for creating checkout, not for watching it complete or anything else */

// use databaseMigrations to avoid creating transactions which will cause OrderedTransactions to throw because it doesn't own the outermost transaction
uses(DatabaseMigrations::class);

beforeEach(function () {
    Queue::fake();

    $this->user = User::factory()->create();

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

    $this->reservation = Reservation::factory()->for($this->offering, 'offering')->for($this->user, 'user')->create([
        'quantity' => 2,
        'status' => ReservationStatus::Pending,
        'expired_at' => now()->addMinutes(10),
    ]);

    $this->checkoutUrl = 'https://checkout.example.test/session';

    $this->gateway = $this->mock(PaymentGateway::class);

    $this->gateway->shouldNotReceive('expireCheckout');

    $this->validateData = function (CreateCheckoutData $data): bool {
        return $data->reservationId === $this->reservation->id
            && $data->unitPrice->minorUnits === 1050
            && $data->unitPrice->currency === Currency::USD
            && $data->quantity === 2
            && $data->connectedAccountId === $this->paymentAccount->provider_account_id;
    };
});

it('creates a payment and redirects to checkout', function () {
    $this->gateway->shouldReceive('provider')
        ->once()
        ->andReturn(PaymentProvider::Stripe);

    $this->gateway->shouldReceive('createCheckout')
        ->once()
        ->withArgs($this->validateData)
        ->andReturn(new CreatedCheckoutData(
            id: 'cs_test',
            url: $this->checkoutUrl,
        ));

    $this->actingAs($this->user)
        ->post(route('reservations.payment.checkout', $this->reservation))
        ->assertRedirect($this->checkoutUrl);

    $payment = $this->reservation->payments()->sole();

    expect($payment->amount)->toBe(2100)
        ->and($payment->currency)->toBe('USD')
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->provider_checkout_id)->toBe('cs_test')
        ->and($payment->company_payment_account_id)
        ->toBe($this->paymentAccount->id)
        ->and($this->reservation->fresh()->status)
        ->toBe(ReservationStatus::Pending);
});

it('reuses the payment and checkout when requested twice', function () {
    $this->gateway->shouldReceive('provider')
        ->twice()
        ->andReturn(PaymentProvider::Stripe);

    $this->gateway->shouldReceive('createCheckout')
        ->twice()
        ->withArgs($this->validateData)
        ->andReturn(new CreatedCheckoutData(
            id: 'cs_test',
            url: $this->checkoutUrl,
        ));

    $this->actingAs($this->user)
        ->post(route('reservations.payment.checkout', $this->reservation))
        ->assertRedirect($this->checkoutUrl);

    $firstPayment = $this->reservation->payments()->sole();

    $this->actingAs($this->user)
        ->post(route('reservations.payment.checkout', $this->reservation))
        ->assertRedirect($this->checkoutUrl);

    $secondPayment = $this->reservation->payments()->sole();

    expect($secondPayment->id)->toBe($firstPayment->id)
        ->and($secondPayment->provider_checkout_id)->toBe('cs_test');
});

test('another user cannot create payment', function () {
    $this->gateway->shouldNotReceive('provider');
    $this->gateway->shouldNotReceive('createCheckout');

    $unauthorizedUser = User::factory()->create();

    $this->actingAs($unauthorizedUser)
        ->post(route('reservations.payment.checkout', $this->reservation))
        ->assertForbidden();
});
