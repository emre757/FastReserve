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
        Schema::create('company_payment_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Team::class);
            $table->string('provider');
            $table->string('provider_account_id');

            $table->string('status')->default('pending'); // see PaymentAccountStatus enum class
            $table->boolean('transfers_enabled')->default(false);

            $table->jsonb('provider_data')->nullable();

            $table->timestampTz('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_account_id']);
        });

        // so that both team_id and provider are unique without affecting closed_at records with same values
        DB::statement(
            'CREATE UNIQUE INDEX company_payment_accounts_current_unique
                 ON company_payment_accounts (team_id, provider)
                 WHERE closed_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_payment_accounts');
    }
};
