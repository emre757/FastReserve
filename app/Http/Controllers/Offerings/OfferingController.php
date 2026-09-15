<?php

namespace App\Http\Controllers\Offerings;

use App\Actions\Offerings\CreateOffering;
use App\Enums\Currency;
use App\Enums\ReservationStatus;
use App\Events\Offerings\OfferingCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Offerings\StoreOfferingRequest;
use App\Http\Requests\Offerings\UpdateOfferingRequest;
use App\Http\Resources\Offerings\IndexResource;
use App\Models\Offering;
use App\Models\Reservation;
use App\Models\Team;
use App\Support\Database\LockContext;
use App\Support\Database\OrderedTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class OfferingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Team $team): Response
    {
        $offerings = $team->offerings()->get();

        return Inertia::render('offerings/index', [
            'company' => $team->only(['name', 'slug']),
            'offerings' => IndexResource::collection($offerings),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(
        StoreOfferingRequest $request,
        CreateOffering $createOffering,
        OrderedTransaction $transactions,
    ): RedirectResponse {
        // remove team context from field as it was only needed to check outdated form
        $data = $request->safe()->except('team_context');

        $offering = $transactions->run(function (LockContext $locks) use ($request, $data, $createOffering): Offering {
            $user = $request->user();
            $offer = $createOffering($user, $data);

            OfferingCreated::dispatch($offer, $user, $request->ip());

            return $offer;
        });

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
            'hasPaymentMethod' => $request->user()->currentTeam->companyPaymentAccounts()->activeMethods()->exists(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    // TODO: cache certain data
    public function show(Request $request, Offering $offering): Response
    {
        Gate::authorize('view', $offering);

        return Inertia::render('offerings/show', [
            'company' => $offering->team->only(['name', 'slug']),
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
                'broadcast_version',
            ]),
            'permissions' => [
                'canUpdateOffering' => Gate::allows('update', $offering),
                'canDeleteOffering' => Gate::allows('delete', $offering),
                'canBook' => Gate::allows('create', [
                    Reservation::class,
                    $offering,
                ]),
            ],
            'reservedSpots' => $offering->reservations()->occupiedSpots()->sum('quantity'),
            'activeReservationId' => $request->user()->reservations()->forOfferingByStatus($offering->id, ReservationStatus::Pending)->first()?->id,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Offering $offering): void
    {
        Gate::authorize('update', $offering);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOfferingRequest $request, Offering $offering): void
    {
        Gate::authorize('update', $offering);
    }

    /**
     * Archive the specified resource from storage.
     */
    public function destroy(Offering $offering): RedirectResponse
    {
        Gate::authorize('delete', $offering);

        $offering->delete();

        return to_route('companies.offerings.index', $offering->team->slug);
    }
}
