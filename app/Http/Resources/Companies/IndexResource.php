<?php

namespace App\Http\Resources\Companies;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Team $team */
        $team = $this->resource;

        return [
            'name' => $team->name,
            'slug' => $team->slug,
            'offerings_count' => $this->whenCounted('offerings'),
            'has_free_offerings' => $this->whenHas('has_free_offerings'),
        ];
    }
}
