<?php

use App\Models\Offering;
use App\Models\User;
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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Offering::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('quantity')->default(1);
            $table->enum('status', ['confirmed', 'pending', 'expired', 'cancelled'])->default('pending');

            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('expired_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();

            // should be set only after confirmation
            $table->string('reference')->nullable()->unique();

            $table->index(['offering_id', 'status']);
            $table->index('status');
            $table->index('reference');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
