<?php

use App\Models\Offering;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Queue::fake(); // for offeringObserver
});

it('deletes archived offerings of 30+ days old', function () {
    $offering = Offering::factory()->create([
        'deleted_at' => now()->subDays(30),
    ]);

    $this->artisan('app:delete-archived-offerings')->assertSuccessful();

    $this->assertDatabaseMissing('offerings', [
        'id' => $offering->id,
    ]);
});

it('skips archived offerings of under 30 days old', function () {
    $offering = Offering::factory()->create([
        'deleted_at' => now()->subDays(29),
    ]);

    $this->artisan('app:delete-archived-offerings')->assertSuccessful();

    $this->assertDatabaseHas('offerings', [
        'id' => $offering->id,
    ]);
});
