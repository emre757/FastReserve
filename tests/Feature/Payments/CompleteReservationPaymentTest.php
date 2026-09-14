<?php

use App\Actions\Payments\CompleteReservationPayment;
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
});

describe('when the reservation can be fulfilled', function () {
    it('confirms the reservation after successful payment', function () {
        $this->freezeSecond(); // freeze time to test timestamps

        app(CompleteReservationPayment::class)->execute(
            checkoutId: $this->payment->provider_checkout_id,
            status: PaymentStatus::Paid,
            paymentIntentId: 'pi_test',
        );

        $reservation = $this->reservation->fresh();
        $payment = $this->payment->fresh();

        expect($reservation->status)->toBe(ReservationStatus::Confirmed)
            ->and($reservation->reference)->not->toBeNull()
            ->and($payment->status)->toBe(PaymentStatus::Paid)
            ->and($payment->provider_payment_id)->toBe('pi_test')
            ->and($payment->paid_at->equalTo(now()))->toBeTrue()
            ->and($reservation->confirmed_at->equalTo(now()))->toBeTrue();

        Notification::assertSentToTimes($this->clientUser, ReservationConfirmed::class);
    });

    it('handles duplicate success without duplicate notifications', function () {
        app(CompleteReservationPayment::class)->execute(
            checkoutId: $this->payment->provider_checkout_id,
            status: PaymentStatus::Paid,
            paymentIntentId: 'pi_test',
        );

        $confirmedAt = $this->reservation->fresh()->confirmed_at;
        $paidAt = $this->payment->fresh()->paid_at;

        app(CompleteReservationPayment::class)->execute(
            checkoutId: $this->payment->provider_checkout_id,
            status: PaymentStatus::Paid,
            paymentIntentId: 'pi_test',
        );

        expect($this->reservation->fresh()->confirmed_at->equalTo($confirmedAt))->toBeTrue()
            ->and($this->payment->fresh()->paid_at->equalTo($paidAt))->toBeTrue();

        Notification::assertSentToTimes($this->clientUser, ReservationConfirmed::class);
    });

    test('failed payment', function () {
        app(CompleteReservationPayment::class)->execute(
            checkoutId: $this->payment->provider_checkout_id,
            status: PaymentStatus::Failed,
            paymentIntentId: 'pi_test',
        );

        $reservation = $this->reservation->fresh();
        $payment = $this->payment->fresh();

        expect($reservation->status)->toBe(ReservationStatus::Pending)
            ->and($reservation->reference)->toBeNull()
            ->and($payment->status)->toBe(PaymentStatus::Failed)
            ->and($payment->provider_payment_id)->toBe('pi_test')
            ->and($payment->paid_at)->toBeNull()
            ->and($reservation->confirmed_at)->toBeNull();

        Notification::assertNotSentTo($this->clientUser, ReservationConfirmed::class);
    });

    test('expired payment', function () {
        app(CompleteReservationPayment::class)->execute(
            checkoutId: $this->payment->provider_checkout_id,
            status: PaymentStatus::Expired,
            paymentIntentId: 'pi_test',
        );

        $reservation = $this->reservation->fresh();
        $payment = $this->payment->fresh();

        expect($reservation->status)->toBe(ReservationStatus::Pending)
            ->and($reservation->reference)->toBeNull()
            ->and($payment->status)->toBe(PaymentStatus::Expired)
            ->and($payment->provider_payment_id)->toBe('pi_test')
            ->and($payment->paid_at)->toBeNull()
            ->and($reservation->confirmed_at)->toBeNull();

        Notification::assertNotSentTo($this->clientUser, ReservationConfirmed::class);
    });

    it(':dataset', function (PaymentStatus $oldStatus, PaymentStatus $newStatus) {
        $this->payment->update(['status' => $oldStatus]);

        $reservationSnapshot = $this->reservation->fresh()->getAttributes();
        $paymentSnapshot = $this->payment->fresh()->getAttributes();

        app(CompleteReservationPayment::class)->execute(
            checkoutId: $this->payment->provider_checkout_id,
            status: $newStatus,
            paymentIntentId: 'pi_test',
        );

        $reservationAttributes = $this->reservation->fresh()->getAttributes();
        $paymentAttributes = $this->payment->fresh()->getAttributes();

        expect($reservationAttributes)->toBe($reservationSnapshot)
            ->and($paymentAttributes)->toBe($paymentSnapshot);

        Notification::assertNotSentTo($this->clientUser, ReservationConfirmed::class);
    })->with([
        'cannot overwrite paid status with expired' => [
            PaymentStatus::Paid,
            PaymentStatus::Expired,
        ],
        'cannot overwrite paid status with failed' => [
            PaymentStatus::Paid,
            PaymentStatus::Failed,
        ],
        'cannot overwrite refunded status' => [
            PaymentStatus::Refunded,
            PaymentStatus::Paid,
        ],
        'cannot overwrite refund pending status' => [
            PaymentStatus::RefundPending,
            PaymentStatus::Paid,
        ],
    ]);

    it('fallbacks if no paymentIntentId given', function () {
        $this->payment->update([
            'provider_payment_id' => 'pi_test2',
        ]);

        app(CompleteReservationPayment::class)->execute(
            checkoutId: $this->payment->provider_checkout_id,
            status: PaymentStatus::Failed,
        );

        expect($this->payment->fresh()->provider_payment_id)
            ->toBe('pi_test2');
    });

    it('confirms the reservation when payment succeeds after failure', function () {
        $action = app(CompleteReservationPayment::class);
        $checkoutId = $this->payment->provider_checkout_id;

        $action->execute($checkoutId, PaymentStatus::Failed);
        $action->execute($checkoutId, PaymentStatus::Paid, 'pi_test');

        expect($this->payment->fresh()->status)->toBe(PaymentStatus::Paid)
            ->and($this->reservation->fresh()->status)
            ->toBe(ReservationStatus::Confirmed);

        Notification::assertSentToTimes(
            $this->clientUser,
            ReservationConfirmed::class
        );
    });
});

// TODO: finish refund feature
describe('when successful payment cannot be fulfilled', function () {
    //        it('handles an inactive offering', func);
    //        it('handles an offering that has already started', func);
    //        it('handles insufficient capacity', ...);
    //        it('handles an already canceled reservation', func);
    //        it('handles a reservation already confirmed by another payment', func);
});
