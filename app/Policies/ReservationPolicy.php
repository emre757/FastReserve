<?php

namespace App\Policies;

use App\Enums\OfferingStatus;
use App\Enums\ReservationStatus;
use App\Models\Offering;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReservationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Offering $offering): Response
    {
        if ($offering->status !== OfferingStatus::Active || $user->cannot('view', $offering)) {
            return Response::deny('Offering is not active');
        }

        if ($offering->booking_deadline_at !== null && $offering->booking_deadline_at <= now()) {
            return Response::deny('Offering booking deadline has passed');
        }

        return $user->reservations()->forOfferingByStatus($offering->id, ReservationStatus::Pending)->doesntExist() ?
            Response::allow() :
            Response::deny('User already has a pending reservation for this offering');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Reservation $reservation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Reservation $reservation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Reservation $reservation): bool
    {
        return false;
    }

    public function makePayment(User $user, Reservation $reservation): Response
    {
        if ($reservation->user->id !== $user->id || $reservation->status !== ReservationStatus::Pending || $reservation->expired_at <= now()) {
            return Response::deny('Reservation either doesnt belong to user, isnt pending or has expired');
        }

        $reservation->load('offering');

        $offering = $reservation->offering;

        return $offering->status === OfferingStatus::Active &&
            $offering->starts_at > now() &&
            (
                $offering->booking_deadline_at === null
                || $offering->booking_deadline_at > now()
            ) ? Response::allow() : Response::deny('Offering is not active, has started or booking deadline has passed');
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $reservation->user->id === $user->id && $reservation->status === ReservationStatus::Pending && $reservation->expired_at > now();
    }
}
