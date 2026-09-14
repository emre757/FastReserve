<?php

namespace App\Models;

use App\Concerns\HasBroadcastVersion;
use App\Contracts\RequiresModelEvents;
use App\Enums\Currency;
use App\Enums\OfferingStatus;
use App\Observers\OfferingObserver;
use Database\Factories\OfferingFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(OfferingObserver::class)]
class Offering extends Model implements RequiresModelEvents
{
    /** @use HasFactory<OfferingFactory> */
    use HasBroadcastVersion, HasFactory, SoftDeletes;

    protected $fillable = [
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
    ];

    // no function but rather the old way due to phpstan otherwise complaining
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'capacity' => 'integer',
        'price' => 'decimal:2',
        'currency' => Currency::class,
        'booking_deadline_at' => 'datetime',
        'cancellation_deadline_at' => 'datetime',
        'hold_duration_minutes' => 'integer',
        'status' => OfferingStatus::class,
    ];

    // get the company/team that owns the offering

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return HasMany<Reservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
