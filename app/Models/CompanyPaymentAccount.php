<?php

namespace App\Models;

use App\Enums\PaymentAccountStatus;
use App\Enums\PaymentProvider;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyPaymentAccount extends Model
{
    protected $fillable = [
        'provider',
        'provider_account_id',
        'status',
        'transfers_enabled',
        'closed_at',
    ];

    /**
     * Get the team that owns this company payment account.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // returns the payment account that is active for the given provider
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function forProvider(
        Builder $query,
        PaymentProvider $provider,
    ): Builder {
        return $query->where('provider', $provider->value);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function activeMethods(Builder $query): Builder
    {
        return $query->whereNull('closed_at')
            ->where('status', PaymentAccountStatus::Active->value)
            ->where('transfers_enabled', true);
    }

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'status' => PaymentAccountStatus::class,
            'transfers_enabled' => 'boolean',
            'provider_data' => 'array',
            'closed_at' => 'datetime',
        ];
    }
}
