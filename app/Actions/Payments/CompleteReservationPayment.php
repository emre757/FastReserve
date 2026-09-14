<?php

namespace App\Actions\Payments;

use App\Enums\OfferingStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Offering;
use App\Models\Payment;
use App\Models\Reservation;
use App\Notifications\Reservations\ReservationConfirmed;
use App\Support\Database\LockContext;
use App\Support\Database\OrderedTransaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Support\Str;

final readonly class CompleteReservationPayment
{
    public function __construct(
        private OrderedTransaction $transactions,
    ) {}

    /**
     * @throws MultipleRecordsFoundException
     * @throws ModelNotFoundException
     * @throws \Throwable
     */
    public function execute(string $checkoutId, PaymentStatus $status, ?string $paymentIntentId = null): void
    {
        if (! in_array($status, [
            PaymentStatus::Paid,
            PaymentStatus::Failed,
            PaymentStatus::Expired,
        ], true)) {
            throw new \InvalidArgumentException(
                "Unsupported payment status: {$status->value}",
            );
        }

        // paid status can overwrite others (besides refund/refundpending) while the rest cannot overwrite paid
        $paymentReference = Payment::query()
            ->select(['id', 'reservation_id'])
            ->where('provider_checkout_id', $checkoutId)
            ->sole();

        $reservationReference = Reservation::query()
            ->select(['id', 'offering_id'])
            ->whereKey($paymentReference->reservation_id)
            ->firstOrFail();

        $this->transactions->run(function (LockContext $locks) use (
            $status,
            $paymentIntentId,
            $checkoutId,
            $paymentReference,
            $reservationReference,
        ): void {
            $offering = $locks->findOrFail(Offering::class, $reservationReference->offering_id);
            $reservation = $locks->findOrFail(Reservation::class, $reservationReference->id);
            $payment = $locks->findOrFail(Payment::class, $paymentReference->id);

            if (
                $payment->reservation_id !== $reservation->id
                || $payment->provider_checkout_id !== $checkoutId
            ) {
                throw new \LogicException('Payment reservation reference changed during processing.');
            }

            // Ignore duplicates or events overwriting paid status.
            if (
                $payment->status === $status
                || $payment->status === PaymentStatus::Paid
                || $payment->status === PaymentStatus::RefundPending
                || $payment->status === PaymentStatus::Refunded
            ) {
                return;
            }

            // TODO: refund if reservation has already been confirmed or cancelled by another payment method
            if (
                $status === PaymentStatus::Paid
                && in_array($reservation->status, [
                    ReservationStatus::Confirmed,
                    ReservationStatus::Cancelled,
                ], true)
            ) {
                // TODO: record payment and arrange refund.
                dd('todo');
            }

            if ($status === PaymentStatus::Paid) {
                $quantity = $reservation->quantity;

                $occupiedByOthers = $offering->reservations()
                    ->occupiedSpots()
                    ->where('id', '!=', $reservation->id)
                    ->sum('quantity');

                $availableSpots = max(0, $offering->capacity - $occupiedByOthers);

                // TODO: check if payment is completed but reservation cannot be made (make refund)
                if (
                    $quantity > $availableSpots
                    || now() >= $offering->starts_at
                    || $offering->status !== OfferingStatus::Active
                ) {
                    dd('TODO: insufficient capacity, offering started, or offering inactive');
                }

                $payment->paid_at = now();
                $reservation->confirmed_at = now();
            }

            $payment->update([
                'provider_payment_id' => $paymentIntentId ?? $payment->provider_payment_id,
                'status' => $status->value,
            ]);

            [$reservationStatus, $reference] = match ($status) {
                PaymentStatus::Paid => [ReservationStatus::Confirmed, (string) Str::ulid()],
                default => [null, null]
            };

            if ($reservationStatus) {
                $reservation->update([
                    'reference' => $reference,
                    'status' => $reservationStatus,
                ]);
            }

            // TODO: refactor & add notifications for other statuses with guards to avoid duplicate notifications
            if ($reservationStatus === ReservationStatus::Confirmed) {
                $reservation->user->notify(new ReservationConfirmed($reservation)->afterCommit());
            }
        });
    }
}
