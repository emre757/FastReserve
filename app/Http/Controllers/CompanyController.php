<?php

namespace App\Http\Controllers;

use App\Http\Requests\Companies\IndexSearchRequest;
use App\Http\Resources\Companies\IndexResource;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

final class CompanyController
{
    public function index(IndexSearchRequest $request): Response
    {
        $data = $request->validated();

        $search = trim($data['search'] ?? '');
        $filters = $data['filters'] ?? [];

        $now = now();

        $openOfferings = fn (Builder $query) => $query
            ->where('status', 'active')
            ->where(function (Builder $query) use ($now) {
                $query->where('booking_deadline_at', '>', $now)
                    ->orWhere(function (Builder $query) use ($now) {
                        $query->whereNull('booking_deadline_at')
                            ->where('starts_at', '>', $now);
                    });
            });

        $teams = Team::query()
            ->when($search != '', fn (Builder $query) => $query->whereLike('name', "%{$search}%"))
            ->when(
                in_array('open', $filters, true)
                || in_array('free', $filters, true),
                function (Builder $query) use ($openOfferings, $filters) {
                    $query->whereHas(
                        'offerings',
                        function (Builder $query) use ($openOfferings, $filters) {
                            $query
                                ->when(
                                    in_array('open', $filters, true),
                                    $openOfferings
                                )
                                ->when(
                                    in_array('free', $filters, true),
                                    fn (Builder $query) => $query->where('price', 0),
                                );
                        },
                    );
                },
            )
            ->withCount(['offerings' => $openOfferings])
            ->withExists([
                'offerings as has_free_offerings' => fn ($query) => $query->where('price', 0),
            ])
            ->orderBy('name')
            ->orderByDesc('id')
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('companies/index', [
            'companies' => IndexResource::collection($teams), // dont name it teams as it'll conflict with global
            'filters' => [
                'search' => $search,
                'filters' => $filters,
            ],
        ]);
    }
}
