<?php

namespace App\Http\Resources\Offerings;

use App\Models\Offering;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Offering $offering */
        $offering = $this->resource;

        return [
            'id' => $offering->id,
            'name' => $offering->name,
            'description' => $offering->description,
            'starts_at' => $offering->starts_at,
            'ends_at' => $offering->ends_at,
            'timezone' => $offering->timezone,
            'capacity' => $offering->capacity,
            'price' => $offering->price,
            'currency' => $offering->currency?->value,
            'status' => $offering->status->value,
        ];
    }
}
