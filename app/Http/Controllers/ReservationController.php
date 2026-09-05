<?php

namespace App\Http\Controllers;

use App\Actions\Reservations\CreateReservation;
use App\Http\Requests\Reservations\StoreReservationRequest;
use App\Http\Requests\Reservations\UpdateReservationRequest;
use App\Http\Resources\Reservations\ShowResource;
use App\Models\Offering;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Offering $offering): Response
    {
        $reservedSpots = (int) $offering->reservations()->occupiedSpots()->sum('quantity');

        return Inertia::render('reservations/create', [
            'offering' => $offering->only('id', 'name', 'capacity', 'price', 'currency'),
            'company' => $offering->team->only('name', 'slug'),
            'availableSpots' => max(0, $offering->capacity - $reservedSpots),
        ]);
    }

    /**
     * Store a newly created resource in storage.
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
    public function show(Reservation $reservation): Response
    {
        $reservation = $reservation->loadMissing('offering.team');

        return Inertia::render('reservations/show', ShowResource::make($reservation)->resolve());
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
}
