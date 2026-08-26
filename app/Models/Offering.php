<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\OfferingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offering extends Model
{
    use SoftDeletes;

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

    // get the company/team that owns the offering

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    protected function casts(): array
    {
        return [
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
    }
}
