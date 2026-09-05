<?php

namespace App\Actions\Reservations;

use App\Enums\ReservationStatus;
use App\Models\Offering;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CreateReservation
{
    /**
     * @throws Throwable
     */
    public function __invoke(User $user, Offering $offering, int $spots): Reservation
    {
        return DB::transaction(function () use ($user, $offering, $spots) {
            $offering = Offering::query()->lockForUpdate()->findOrFail($offering->id);

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

            return $reservation;
        });
    }
}
