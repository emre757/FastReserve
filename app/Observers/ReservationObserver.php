<?php

namespace App\Observers;

use App\Events\Offerings\OfferingAvailabilityChanged;
use App\Models\Offering;
use App\Models\Reservation;
use App\Support\Database\LockContext;
use App\Support\Database\OrderedTransaction;

final class ReservationObserver
{
    public function __construct(
        private readonly OrderedTransaction $transactions,
    ) {}

    private function broadcastChange(Reservation $reservation): void
    {
        $this->transactions->run(function (LockContext $locks) use ($reservation): void {
            $offering = $locks->findOrFail(Offering::class, $reservation->offering_id);

            $version = $offering->incrementBroadcastVersion();

            $occupiedSpots = $offering->reservations()->occupiedSpots()->sum('quantity');

            OfferingAvailabilityChanged::dispatch(
                $offering->id,
                (int) max(0, $offering->capacity - $occupiedSpots),
                $version,
            );
        });
    }

    /**
     * Handle the Reservation "created" event.
     */
    public function created(Reservation $reservation): void
    {
        $this->broadcastChange($reservation);
    }

    /**
     * Handle the Reservation "updated" event.
     */
    public function updated(Reservation $reservation): void
    {
        if (! $reservation->wasChanged(['status', 'quantity', 'expired_at'])) {
            return;
        }

        $this->broadcastChange($reservation);
    }

    /**
     * Handle the Reservation "deleted" event.
     */
    public function deleted(Reservation $reservation): void
    {
        $this->broadcastChange($reservation);
    }

    /**
     * Handle the Reservation "restored" event.
     */
    public function restored(Reservation $reservation): void
    {
        //
    }

    /**
     * Handle the Reservation "force deleted" event.
     */
    public function forceDeleted(Reservation $reservation): void
    {
        //
    }
}
