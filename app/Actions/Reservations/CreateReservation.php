<?php

namespace App\Actions\Reservations;

use App\Enums\ReservationStatus;
use App\Jobs\Reservations\ExpireReservation;
use App\Models\Offering;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Database\LockContext;
use App\Support\Database\OrderedTransaction;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class CreateReservation
{
    public function __construct(
        private OrderedTransaction $transactions,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(User $user, Offering $offering, int $spots): Reservation
    {
        return $this->transactions->run(function (LockContext $locks) use ($user, $offering, $spots): Reservation {
            $offering = $locks->findOrFail(Offering::class, $offering->id);

            $occupiedSpots = $offering->reservations()->occupiedSpots()->sum('quantity');
            $availableSpots = max(0, $offering->capacity - $occupiedSpots);

            if ($availableSpots < $spots) {
                throw ValidationException::withMessages([
                    'spots' => "Not enough spots available. (max: $availableSpots)",
                ]);
            }

            $reservation = $offering->reservations()->make([
                'quantity' => $spots,
                'status' => ReservationStatus::Pending,
                'expired_at' => now()->addMinutes($offering->hold_duration_minutes),
            ]);

            $reservation->user()->associate($user);
            $reservation->save();

            ExpireReservation::dispatch($reservation->id)
                ->delay($reservation->expired_at)
                ->afterCommit();

            return $reservation;
        });
    }
}
