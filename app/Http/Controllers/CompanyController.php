<?php

namespace App\Http\Controllers;

use App\Http\Resources\Companies\IndexResource;
use App\Models\Team;
use Inertia\Inertia;

final class CompanyController
{
    public function index()
    {
        $teams = Team::query()
            ->withCount('offerings')
            ->withExists([
                'offerings as has_free_offerings' => fn ($query) => $query->where('price', 0),
            ])
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('companies/index', [
            'companies' => IndexResource::collection($teams), // dont name it teams as it'll conflict with global
        ]);
    }
}
