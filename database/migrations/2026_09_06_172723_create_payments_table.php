<?php

use App\Models\CompanyPaymentAccount;
use App\Models\Reservation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Reservation::class)
                ->constrained()
                ->restrictOnDelete();

            $table->foreignIdFor(CompanyPaymentAccount::class)
                ->constrained()
                ->restrictOnDelete();

            $table->string('provider_checkout_id')->nullable()->unique();
            $table->string('provider_payment_id')->nullable()->unique();
            $table->string('provider_refund_id')->nullable()->unique();

            $table->unsignedBigInteger('amount'); // $25.50 -> 2550
            $table->string('currency', 3);

            $table->string('status')->default('creating')->index();
            $table->string('refund_reason')->nullable();

            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('refund_requested_at')->nullable();
            $table->timestampTz('refunded_at')->nullable();

            $table->timestamps();

            $table->index('reservation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
