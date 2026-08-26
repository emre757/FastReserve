<?php

namespace App\Http\Controllers\Offerings;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Offerings\StoreOfferingRequest;
use App\Http\Requests\Offerings\UpdateOfferingRequest;
use App\Models\Offering;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class OfferingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfferingRequest $request): RedirectResponse
    {
        // remove team context from field as it was only needed to check outdated form
        $data = $request->safe()->except('team_context');

        // convert all dates to the correct format  & to utc
        foreach ([
            'starts_at',
            'ends_at',
            'booking_deadline_at',
            'cancellation_deadline_at',
        ] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = Carbon::createFromFormat(
                    '!Y-m-d\TH:i',
                    $data[$field],
                    $data['timezone'],
                )->utc();
            }
        }

        $offering = $request->user()
            ->currentTeam
            ->offerings()
            ->create($data);

        return to_route('offerings.show', $offering);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', [Offering::class, $request->user()->currentTeam]);

        return Inertia::render('offerings/create-offering-form', [
            'timezones' => \DateTimeZone::listIdentifiers(),
            'currencies' => Currency::cases(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Offering $offering): Response
    {
        Gate::authorize('view', $offering);

        return Inertia::render('offerings/show-offering', [
            'offering' => $offering->only([
                'id',
                'name',
                'description',
                'starts_at',
                'ends_at',
                'timezone',
                'capacity',
                'price',
                'currency',
                'booking_deadline_at',
                'cancellation_deadline_at',
                'hold_duration_minutes',
                'status',
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Offering $offering): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOfferingRequest $request, Offering $offering): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Offering $offering): void
    {
        //
    }
}
