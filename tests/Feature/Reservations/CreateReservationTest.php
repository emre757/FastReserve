<?php

use App\Enums\OfferingStatus;
use App\Enums\ReservationStatus;
use App\Jobs\Reservations\ExpireReservation as ExpireReservationJob;
use App\Models\Offering;
use App\Models\Reservation;
use App\Models\User;
use App\Policies\OfferingPolicy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Queue::fake();

    $this->freezeSecond();

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->assertExpireReservationJobDispatched = function ($expiredAt): void {
        Queue::assertPushedOnce(ExpireReservationJob::class);

        Queue::assertPushed(
            ExpireReservationJob::class,
            function (ExpireReservationJob $job) use ($expiredAt): bool {
                return $job->delay->equalTo($expiredAt);
            },
        );
    };
});

describe('happy path', function () {
    it('creates reservation with valid data', function () {
        $offering = Offering::factory()->create([
            'capacity' => 10,
        ]);

        $this->post("/offerings/{$offering->id}/reservations", [
            'spots' => 2,
        ])->assertRedirect(route('reservations.show', 1));

        $reservation = Reservation::where([
            'offering_id' => $offering->id,
            'user_id' => $this->user->id,
            'quantity' => 2,
        ])->sole();

        $this->assertEquals(2, $offering->reservations()->occupiedSpots()->sum('quantity'));
        ($this->assertExpireReservationJobDispatched)($reservation->expired_at);
    });

    it('creates reservation if user has non-pending reservation', function () {
        $offering = Offering::factory()->create([
            'capacity' => 10,
        ]);

        Reservation::factory()->for($this->user)->for($offering)->create([
            'status' => ReservationStatus::Cancelled,
        ]);

        $this->post("/offerings/{$offering->id}/reservations", [
            'spots' => 2,
        ])->assertRedirect(route('reservations.show', 2));

        $this->assertDatabaseCount('reservations', 2);
        $reservation = Reservation::where([
            'offering_id' => $offering->id,
            'user_id' => $this->user->id,
            'quantity' => 2,
            'status' => ReservationStatus::Pending,
        ])->sole();

        $this->assertEquals(2, $offering->reservations()->occupiedSpots()->sum('quantity'));
        ($this->assertExpireReservationJobDispatched)($reservation->expired_at);
    });
});

describe('unhappy path', function () {
    it('rejects a reservation exceeding the remaining capacity', function () {
        $offering = Offering::factory()->create([
            'capacity' => 10,
        ]);

        $this->post("/offerings/{$offering->id}/reservations", [
            'spots' => 9,
        ])->assertFound();

        $newUser = User::factory()->create();

        $this->actingAs($newUser)->post("/offerings/{$offering->id}/reservations", [
            'spots' => 2,
        ])->assertSessionHasErrors('spots');

        $this->assertDatabaseCount('reservations', 1);
        $reservation = Reservation::where([
            'offering_id' => $offering->id,
            'user_id' => $this->user->id,
            'quantity' => 9,
            'status' => ReservationStatus::Pending,
        ])->sole();

        $this->assertEquals(9, $offering->reservations()->occupiedSpots()->sum('quantity'));
        ($this->assertExpireReservationJobDispatched)($reservation->expired_at);
    });

    it('rejects invalid quantity datasets', function (string|float|int $quantity) {
        $offering = Offering::factory()->create();

        $this->post("/offerings/{$offering->id}/reservations", [
            'spots' => $quantity,
        ])->assertSessionHasErrors('spots');
    })->with(['abc', 1.5, 0, -1]);

    it('cannot create reservation for non-active offering', function (OfferingStatus $offeringStatus) {
        $offering = Offering::factory()->create([
            'capacity' => 10,
            'status' => $offeringStatus,
        ]);

        $this->post(route('offerings.reservations.store', $offering), [
            'spots' => 2,
        ])->assertForbidden();

        $this->assertDatabaseEmpty('reservations');
        Queue::assertNotPushed(ExpireReservationJob::class);
    })->with([OfferingStatus::Completed, OfferingStatus::Cancelled]);

    it('cannot create reservation if booking deadline is met', function () {
        $offering = Offering::factory()->create([
            'capacity' => 10,
            'status' => OfferingStatus::Active,
            'booking_deadline_at' => now()->subHour(),
        ]);

        $this->post(route('offerings.reservations.store', $offering), [
            'spots' => 2,
        ])->assertForbidden();

        $this->assertDatabaseEmpty('reservations');
        Queue::assertNotPushed(ExpireReservationJob::class);
    });

    it('prevents booking an offering the user cannot view', function () {
        $offering = Offering::factory()->create([
            'capacity' => 10,
        ]);

        $this->mock(OfferingPolicy::class)
            ->shouldReceive('view')
            ->once()
            ->andReturn(false);

        $this->post(route('offerings.reservations.store', $offering), [
            'spots' => 2,
        ])->assertForbidden();

        $this->assertDatabaseEmpty('reservations');
        Queue::assertNotPushed(ExpireReservationJob::class);
    });

    it('prevents creating another reservation if user already has an pending', function () {
        $offering = Offering::factory()->create([
            'capacity' => 10,
        ]);

        Reservation::factory()->for($this->user)->for($offering)->create();

        $this->post(route('offerings.reservations.store', $offering), [
            'spots' => 2,
        ])->assertForbidden();

        $this->assertDatabaseCount('reservations', 1);
        Queue::assertNotPushed(ExpireReservationJob::class);
    });
});
