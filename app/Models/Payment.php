<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'company_payment_account_id',
        'provider_checkout_id',
        'amount',
        'currency',
        'status',
        'provider_payment_id',
        'paid_at',
    ];

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /** @return BelongsTo<CompanyPaymentAccount, $this> */
    public function companyPaymentAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyPaymentAccount::class);
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, [
            PaymentStatus::Creating,
            PaymentStatus::Pending,
        ], true);
    }

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }
}
