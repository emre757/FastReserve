<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_member_roles_can_be_updated_by_owners()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($owner)
            ->patch(route('teams.members.update', [$team, $member]), [
                'role' => TeamRole::Admin->value,
            ]);

        $response->assertRedirect(route('teams.edit', $team));

        $this->assertEquals(
            TeamRole::Admin->value,
            $team->members()->where('user_id', $member->id)->first()->pivot->role->value,
        );
    }

    public function test_team_member_roles_cannot_be_updated_by_non_owners()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('teams.members.update', [$team, $member]), [
                'role' => TeamRole::Admin->value,
            ]);

        $response->assertForbidden();
    }

    public function test_team_members_can_be_removed_by_owners()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($owner)
            ->delete(route('teams.members.destroy', [$team, $member]));

        $response->assertRedirect(route('teams.edit', $team));

        $this->assertFalse($member->fresh()->belongsToTeam($team));
    }

    public function test_team_members_cannot_be_removed_by_non_owners()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('teams.members.destroy', [$team, $member]));

        $response->assertForbidden();
    }

    public function test_team_owner_cannot_be_removed()
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($owner)
            ->delete(route('teams.members.destroy', [$team, $owner]));

        $response->assertForbidden();

        $this->assertTrue($owner->fresh()->belongsToTeam($team));
    }

    public function test_team_member_role_cannot_be_set_to_owner()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($owner)
            ->patch(route('teams.members.update', [$team, $member]), [
                'role' => TeamRole::Owner->value,
            ]);

        $response->assertSessionHasErrors('role');

        $this->assertEquals(
            TeamRole::Member->value,
            $team->members()->where('user_id', $member->id)->first()->pivot->role->value,
        );
    }

    public function test_removing_a_members_final_current_team_clears_the_current_team()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $member->update(['current_team_id' => $team->id]);

        $this
            ->actingAs($owner)
            ->delete(route('teams.members.destroy', [$team, $member]));

        $this->assertNull($member->fresh()->current_team_id);
    }

    public function test_removing_a_members_current_team_switches_to_another_team()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $removedTeam = Team::factory()->create(['name' => 'Zulu Team']);
        $fallbackTeam = Team::factory()->create(['name' => 'Alpha Team']);

        $removedTeam->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $removedTeam->members()->attach($member, ['role' => TeamRole::Member->value]);
        $fallbackTeam->members()->attach($member, ['role' => TeamRole::Member->value]);

        $member->update(['current_team_id' => $removedTeam->id]);

        $this
            ->actingAs($owner)
            ->delete(route('teams.members.destroy', [$removedTeam, $member]));

        $this->assertEquals($fallbackTeam->id, $member->fresh()->current_team_id);
    }
}
