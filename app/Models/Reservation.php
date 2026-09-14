<?php

namespace App\Models;

use App\Contracts\RequiresModelEvents;
use App\Enums\ReservationStatus;
use App\Observers\ReservationObserver;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(ReservationObserver::class)]
class Reservation extends Model implements RequiresModelEvents // cannot update directly through SQL query, must interact with model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    protected $fillable = [
        'quantity',
        'status',
        'expired_at',
        'reference',
        'confirmed_at',
        'cancelled_at',
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

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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

    /**
     * @param  Builder<Reservation>  $query
     * @return Builder<Reservation>
     */
    #[Scope]
    protected function forOfferingByStatus(
        Builder $query,
        int $offeringId,
        ReservationStatus $status,
    ): Builder {
        return $query
            ->where('offering_id', $offeringId)
            ->where('status', $status);
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
