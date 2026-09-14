<?php

namespace App\Actions\Reservations;

use App\Contracts\Payments\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Offering;
use App\Models\Payment;
use App\Models\Reservation;
use App\Support\Database\LockContext;
use App\Support\Database\OrderedTransaction;

final readonly class ExpireReservation
{
    public function __construct(
        private PaymentGateway $paymentGateway,
        private OrderedTransaction $transactions,
    ) {}

    public function execute(Reservation $reservation, ReservationStatus $newStatus): void
    {
        $this->transactions->run(function (LockContext $locks) use ($reservation, $newStatus): void {
            $locks->findOrFail(Offering::class, $reservation->offering_id);
            $reservation = $locks->findOrFail(Reservation::class, $reservation->id);

            // recheck reservation status as it might have been changed by another request
            if ($reservation->status !== ReservationStatus::Pending) {
                return;
            }

            if (
                $newStatus === ReservationStatus::Expired
                && (
                    $reservation->expired_at === null
                    || $reservation->expired_at > now()
                )
            ) {
                return;
            }

            $paymentIds = $reservation->payments()
                ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Creating])
                ->orderBy('id')
                ->pluck('id');

            $paymentIds->each(function (int $paymentId) use ($locks, $newStatus): void {
                $payment = $locks->findOrFail(Payment::class, $paymentId);

                if ($payment->provider_checkout_id) {
                    $this->paymentGateway->expireCheckout($payment->provider_checkout_id);
                }

                $payment->update([
                    'status' => $newStatus === ReservationStatus::Expired
                        ? PaymentStatus::Expired
                        : PaymentStatus::Failed,
                ]);
            });

            $reservation->update([
                'status' => $newStatus,
                ...match ($newStatus) {
                    ReservationStatus::Cancelled => ['cancelled_at' => now()],
                    default => [],
                },
            ]);
        });
    }
}
