<?php

use App\Models\Team;
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
        Schema::create('offerings', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Team::class)->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();

            $table->timestampTz('starts_at'); // exact date and time, timezone-aware
            $table->timestampTz('ends_at')->nullable();
            $table->string('timezone')->default('Europe/Amsterdam');

            $table->unsignedInteger('capacity');
            $table->decimal('price'); // can be free (8 digits & 2 decimals as per default value)
            $table->char('currency', 3)->nullable(); // can be free (currencies must have max 3 chars)

            $table->timestampTz('booking_deadline_at')->nullable(); // host may want to lock bookings before offering starts
            $table->timestampTz('cancellation_deadline_at')->nullable(); // what the deadline is for cancelling
            $table->unsignedSmallInteger('hold_duration_minutes')->default(10); // max minutes someone can hold a spot before paying

            $table->string('status')->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status', 'starts_at']);
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offerings');
    }
};
