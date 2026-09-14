<?php

namespace App\Jobs\Reservations;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ExpireReservation implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $reservationId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Actions\Reservations\ExpireReservation $expireReservationAction): void
    {
        $reservation = Reservation::query()->find($this->reservationId);

        if ($reservation === null) {
            return;
        }

        // even though action does this check, check before executing to avoid extra query from action
        if (
            $reservation->status !== ReservationStatus::Pending
            || $reservation->expired_at === null
            || $reservation->expired_at > now()
        ) {
            return;
        }

        $expireReservationAction->execute($reservation, ReservationStatus::Expired);
    }
}
