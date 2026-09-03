<?php

namespace App\Http\Controllers;

use App\Http\Resources\Companies\IndexResource;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CompanyController
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim();
        $filters = array_values(array_intersect(
            $request->array('filters'),
            ['active', 'free']
        ));

        $teams = Team::query()
            ->when($search != '', fn (Builder $query) => $query->whereLike('name', "%{$search}%"))
            ->when(
                in_array('active', $filters, true)
                || in_array('free', $filters, true),
                function (Builder $query) use ($filters) {
                    $query->whereHas(
                        'offerings',
                        function (Builder $query) use ($filters) {
                            $query
                                ->when(
                                    in_array('active', $filters, true),
                                    fn (Builder $query) => $query->where('status', 'active'),
                                )
                                ->when(
                                    in_array('free', $filters, true),
                                    fn (Builder $query) => $query->where('price', 0),
                                );
                        },
                    );
                },
            )
            ->withCount('offerings')
            ->withExists([
                'offerings as has_free_offerings' => fn ($query) => $query->where('price', 0),
            ])
            ->orderBy('name')
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
