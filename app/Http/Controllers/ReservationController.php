<?php

namespace App\Http\Controllers;

use App\Actions\Reservations\CreateReservation;
use App\Actions\Reservations\ExpireReservation;
use App\Contracts\Payments\PaymentGateway;
use App\Enums\ReservationStatus;
use App\Http\Requests\Reservations\StoreReservationRequest;
use App\Http\Requests\Reservations\UpdateReservationRequest;
use App\Http\Resources\Reservations\ShowResource;
use App\Models\Offering;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class ReservationController extends Controller
{
    /**
     * List all reservations for the offer.
     */
    public function index(): void
    {
        //
    }

    /**
     * List all reservations for the current user.
     */
    public function userIndex(Request $request): Response
    {
        $reservations = $request->user()->reservations()->get(['id']);

        return Inertia::render('reservations/index', [
            'Reservations' => $reservations,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Offering $offering): Response
    {
        \Gate::authorize('create', [Reservation::class, $offering]);

        $reservedSpots = (int) $offering->reservations()->occupiedSpots()->sum('quantity');

        return Inertia::render('reservations/create', [
            'offering' => $offering->only('id', 'name', 'capacity', 'price', 'currency', 'broadcast_version'),
            'company' => $offering->team->only('name', 'slug'),
            'availableSpots' => max(0, $offering->capacity - $reservedSpots),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(StoreReservationRequest $request, Offering $offering, CreateReservation $createReservation): RedirectResponse
    {
        $reservation = $createReservation($request->user(), $offering, $request->integer('spots'));

        return to_route('reservations.show', $reservation);
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation, PaymentGateway $paymentGateway): Response
    {
        \Gate::authorize('view', $reservation);

        $reservation = $reservation->loadMissing('offering.team');

        return Inertia::render('reservations/show', ShowResource::make($reservation, $paymentGateway)->resolve());
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reservation $reservation): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation): void
    {
        //
    }

    public function cancel(Reservation $reservation, ExpireReservation $expireReservationAction): RedirectResponse
    {
        \Gate::authorize('cancel', $reservation);

        $expireReservationAction->execute($reservation, ReservationStatus::Cancelled);

        return to_route('reservations.show', $reservation);
    }
}
