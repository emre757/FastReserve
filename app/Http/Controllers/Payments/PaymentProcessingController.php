<?php

namespace App\Http\Controllers\Payments;

use App\Models\Reservation;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class PaymentProcessingController
{
    public function __invoke(Reservation $reservation): Response
    {
        Gate::authorize('view', $reservation);

        $reservation->loadMissing('offering.team');

        return Inertia::render('payments/processing', [
            'reservation' => $reservation->only(['id', 'status']),
            'offering' => $reservation->offering->only('name'),
            'company' => $reservation->offering->team->only('name'),
        ]);
    }
}
