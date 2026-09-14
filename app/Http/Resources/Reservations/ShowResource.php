<?php

namespace App\Http\Resources\Reservations;

use App\Models\Reservation;
use App\Payments\Money;
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

        $offering = $reservation->offering;
        $team = $offering->team;
        $amountDue = $offering->currency === null
            ? '0.00'
            : Money::fromDecimal($offering->price, $offering->currency)
                ->multiply($reservation->quantity)
                ->decimal();

        return [
            'reservation' => [
                'id' => $reservation->id,
                'reference' => $reservation->reference,
                'status' => $reservation->status->value,
                'quantity' => $reservation->quantity,
                'amount_due' => $amountDue,
                'created_at' => $reservation->created_at->toISOString(),
                'expired_at' => $reservation->expired_at?->toISOString(),
                'confirmed_at' => $reservation->confirmed_at?->toISOString(),
                'cancelled_at' => $reservation->cancelled_at?->toISOString(),
            ],
            'offering' => [
                'id' => $offering->id,
                'name' => $offering->name,
                'price' => $offering->price,
                'currency' => $offering->currency?->value,
                'starts_at' => $offering->starts_at->toISOString(),
                'timezone' => $offering->timezone,
            ],
            'company' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'serverTime' => now()->toISOString(),
            'paymentMethods' => $team->companyPaymentAccounts()
                ->activeMethods()
                ->pluck('provider')
                ->values()
                ->all(),
        ];
    }
}
