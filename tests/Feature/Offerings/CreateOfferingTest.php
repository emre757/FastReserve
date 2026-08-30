<?php

use App\Enums\AuditAction;
use App\Enums\Currency;
use App\Enums\TeamRole;
use App\Models\Offering;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(LazilyRefreshDatabase::class);

// create default payload, can be replaced by passing new value definitions in overrides parameter
/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validOfferingPayload(string $teamSlug, array $overrides = []): array
{
    $timezone = 'Europe/Amsterdam';

    $startsAt = CarbonImmutable::now($timezone)
        ->addWeek()
        ->startOfHour();

    return array_merge([
        'team_context' => $teamSlug,
        'name' => fake()->word(),
        'description' => fake()->sentence(),
        'starts_at' => $startsAt->format('Y-m-d\TH:i'),
        'ends_at' => $startsAt->addDay()->format('Y-m-d\TH:i'),
        'timezone' => $timezone,
        'capacity' => fake()->randomDigitNotNull(),
        'price' => fake()->randomFloat(2, 1, 999),
        // store form should remove currency if price is 0
        'currency' => fake()->randomElement(Currency::cases())->value,
        'booking_deadline_at' => $startsAt->subDay()->format('Y-m-d\TH:i'),
        'cancellation_deadline_at' => $startsAt->subDays(2)->format('Y-m-d\TH:i'),
    ], $overrides);
}

describe('Happy path create offering', function () {
    beforeEach(function () {
        $this->user = User::factory()->withTeam()->create()->fresh();
        $this->team = $this->user->currentTeam;

        $this->actingAs($this->user);
    });

    it('shows the create offering page to authorized users', function () {
        $this->get(route('offerings.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page->component('offerings/create-offering-form'));
    });

    it('creates a paid offering and redirects to its page', function () {
        $timezone = 'Europe/Amsterdam';

        $startsAt = CarbonImmutable::now($timezone)
            ->addWeek()
            ->startOfHour();

        $endsAt = $startsAt->addDay();
        $deadline = $startsAt->subDay();

        $payload = validOfferingPayload($this->team->slug, [
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'ends_at' => $endsAt->format('Y-m-d\TH:i'),
            'timezone' => $timezone,
            'booking_deadline_at' => $deadline->format('Y-m-d\TH:i'),
            'cancellation_deadline_at' => $deadline->format('Y-m-d\TH:i'),
            'hold_duration_minutes' => '', // check if it'll be the default value (10) if null
        ]);

        $response = $this->post(route('offerings.store'), $payload);

        $response->assertRedirect()->assertSessionHasNoErrors();

        $offering = Offering::query()->whereTeamId($this->team->id)->sole();

        // verify timezone & all date fields
        expect($offering)->toBeInstanceOf(Offering::class)
            ->and($offering->timezone)->toBe($payload['timezone'])
            ->and($offering->starts_at->equalTo($startsAt->utc()))->toBeTrue()
            ->and($offering->ends_at->equalTo($endsAt->utc()))->toBeTrue()
            ->and($offering->booking_deadline_at->equalTo($deadline->utc()))->toBeTrue()
            ->and($offering->cancellation_deadline_at->equalTo($deadline->utc()))->toBeTrue()
            ->and($offering->hold_duration_minutes)->toBe(10); // 10 is the set default value in migration

        $response->assertRedirect(route('offerings.show', $offering));

        // check if audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->user->id,
            'actor_type' => $this->user->getMorphClass(),
            'subject_id' => $offering->id,
            'subject_type' => $offering->getMorphClass(),
            'action' => AuditAction::OfferingCreated,
        ]);
    });

    it('creates a free offering and redirects to its page', function () {
        $payload = validOfferingPayload($this->team->slug, ['price' => 0]);

        $response = $this->post(route('offerings.store'), $payload);

        $response->assertRedirect()->assertSessionHasNoErrors();

        $offering = Offering::query()
            ->whereTeamId($this->team->id)
            ->sole();

        expect($offering)->toBeInstanceOf(Offering::class)
            ->and($offering->price)->toBe('0.00')
            ->and($offering->currency)->toBeNull();

        $response->assertRedirect(route('offerings.show', $offering));
    });
});

it('forbids showing create page to users without a team', function () {
    $user = User::factory()->create()->fresh();

    $this->actingAs($user)->get(route('offerings.create'))->assertForbidden();
});

it('forbids showing the create page to team members without create permission', function () {
    $owner = User::factory()->withTeam()->create()->fresh();
    $team = $owner->currentTeam;

    $member = User::factory()->create();

    // attach/add to team first then switch team
    $team->members()->attach($member, [
        'role' => TeamRole::Member->value,
    ]);

    // only changes current team
    $member->switchTeam($team);

    $this->actingAs($member)
        ->get(route('offerings.create'))
        ->assertForbidden();
});

describe('Unhappy path create offering', function () {
    beforeEach(function () {
        $this->user = User::factory()->withTeam()->create()->fresh();
        $this->team = $this->user->currentTeam;

        $this->actingAs($this->user);
    });

    test('request with outdated form', function () {
        $this->post(route('offerings.store'), validOfferingPayload('invalid-team-id'))->assertForbidden();
    });

    test('request with invalid permission', function () {
        $owner = User::factory()->withTeam()->create()->fresh();
        $team = $owner->currentTeam;

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('offerings.store'), validOfferingPayload($team->slug))->assertForbidden();

        // also check if it is the same for members with insufficient permission
        $team->members()->attach($user, [
            'role' => TeamRole::Member->value,
        ]);

        $user->switchTeam($team);

        $this->actingAs($user)->post(route('offerings.store'), validOfferingPayload($team->slug))->assertForbidden();
    });

    test('request with invalid payload fields', function (array $overrides, array $expectedErrors) {
        $payload = validOfferingPayload($this->team->slug, $overrides);

        $this->post(route('offerings.store'), $payload)->assertSessionHasErrors($expectedErrors);
    })->with([
        'invalid data' => [
            // $overrides
            [
                'name' => str_repeat('a', 260),
                'starts_at' => null,
                'ends_at' => '202020-02-210',
                'timezone' => 'FAKE',
                'capacity' => null,
                'price' => -2,
                'currency' => 'EEE',
                'booking_deadline_at' => 'not real',
                'cancellation_deadline_at' => '000-000-000',
                'hold_duration_minutes' => 200,
            ],

            // $expectedErrors
            ['name', 'starts_at', 'ends_at', 'timezone', 'capacity', 'price', 'currency', 'booking_deadline_at', 'cancellation_deadline_at', 'hold_duration_minutes'],
        ],
        'dates are set in the wrong order' => [
            [
                'starts_at' => '2026-09-10T10:00', // this passes
                'ends_at' => '2026-09-10T09:00',
                'booking_deadline_at' => '2026-09-10T11:00',
                'cancellation_deadline_at' => '2026-09-11T10:00',
            ],

            ['ends_at', 'booking_deadline_at', 'cancellation_deadline_at'],
        ],
    ]);
});
