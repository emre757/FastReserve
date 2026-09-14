<?php

namespace App\Http\Controllers\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Data\Payments\CreateCheckoutData;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Reservation;
use App\Payments\Money;
use App\Support\Database\LockContext;
use App\Support\Database\OrderedTransaction;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class ReservationPaymentController
{
    public function store(
        Reservation $reservation,
        PaymentGateway $gateway,
        OrderedTransaction $transactions,
    ): Response {
        \Gate::authorize('make-payment', $reservation);

        $reservation->loadMissing('offering.team');
        $offering = $reservation->offering;

        $unitPrice = Money::fromDecimal($offering->price, $offering->currency);

        if ($unitPrice->minorUnits === 0) {
            throw new \LogicException('Cannot create a payment for an offering with a price of zero. (wrong method called)');
        }

        $paymentAccount = $offering->team
            ->companyPaymentAccounts()
            ->forProvider($gateway->provider())
            ->activeMethods()
            ->sole();

        $payment = $transactions->run(function (LockContext $locks) use (
            $reservation,
            $paymentAccount,
            $unitPrice,
        ): Payment {
            $reservation = $locks->findOrFail(Reservation::class, $reservation->id);

            \Gate::authorize('make-payment', $reservation);

            $payment = $reservation->payments()
                ->whereIn('status', [
                    PaymentStatus::Creating->value,
                    PaymentStatus::Pending->value,
                ])
                ->first();

            if ($payment !== null) {
                return $locks->findOrFail(Payment::class, $payment->id);
            }

            return $reservation->payments()->create([
                'company_payment_account_id' => $paymentAccount->id,
                'amount' => $unitPrice
                    ->multiply($reservation->quantity)
                    ->minorUnits,
                'currency' => $unitPrice->currency->value,
                'status' => PaymentStatus::Creating->value,
            ]);
        });

        $checkout = $gateway->createCheckout(new CreateCheckoutData(
            $payment->id,
            $unitPrice,
            $reservation->quantity,
            $reservation->offering->name,
            $reservation->id,
            $reservation->offering_id,
            $reservation->offering->team_id,
            $paymentAccount->provider_account_id
        ));

        // payment status might be changed while checkout was being created
        $payment = $transactions->run(function (LockContext $locks) use ($payment, $checkout): Payment {
            $payment = $locks->findOrFail(Payment::class, $payment->id);

            if ($checkout->id !== null) {
                $payment->provider_checkout_id = $checkout->id;

                if ($payment->status === PaymentStatus::Creating) {
                    $payment->status = PaymentStatus::Pending;
                }
            }

            $payment->save();

            return $payment;
        });

        // check if payment status changed after checkout was created
        if ($payment->status !== PaymentStatus::Pending) {
            if (! in_array($payment->status, [
                PaymentStatus::Paid,
                PaymentStatus::RefundPending,
                PaymentStatus::Refunded,
            ])) {
                if ($checkout->id !== null) {
                    $gateway->expireCheckout($checkout->id);
                }
            }

            return to_route('reservations.show', $reservation);
        }

        return Inertia::location($checkout->url);
    }
}
