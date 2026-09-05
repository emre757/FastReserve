<?php

namespace App\Http\Resources\Reservations;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Reservation $reservation */
        $reservation = $this->resource;

        return [
            'reservation' => [
                'id' => $reservation->id,
                'reference' => $reservation->reference,
                'status' => $reservation->status->value,
                'quantity' => $reservation->quantity,
                'amount_due' => $reservation->quantity * $reservation->offering->price,
                'created_at' => $reservation->created_at->toISOString(),
                'expired_at' => $reservation->expired_at?->toISOString(),
                'confirmed_at' => $reservation->confirmed_at?->toISOString(),
                'cancelled_at' => $reservation->cancelled_at?->toISOString(),
            ],
            'offering' => [
                'id' => $reservation->offering->id,
                'name' => $reservation->offering->name,
                'price' => $reservation->offering->price,
                'currency' => $reservation->offering->currency?->value,
                'starts_at' => $reservation->offering->starts_at->toISOString(),
                'timezone' => $reservation->offering->timezone,
            ],
            'company' => [
                'name' => $reservation->offering->team->name,
                'slug' => $reservation->offering->team->slug,
            ],
            'serverTime' => now()->toISOString(),
        ];
    }
}
