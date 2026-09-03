<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'quantity',
        'status',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Offering, $this> */
    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    // use ->sum('quantity') to count how many spots taken
    /**
     * @param  Builder<Reservation>  $query
     * @return Builder<Reservation>
     */
    #[Scope]
    protected function occupiedSpots(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query
                ->where('status', ReservationStatus::Confirmed)
                ->orWhere(function (Builder $query) {
                    $query
                        ->where('status', ReservationStatus::Pending)
                        ->where('expired_at', '>', now());
                });
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => ReservationStatus::class,
            'confirmed_at' => 'datetime',
            'expired_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
