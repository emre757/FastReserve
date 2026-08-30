<?php

namespace Database\Seeders;

use App\Models\Offering;
use App\Models\Team;
use Illuminate\Database\Seeder;

// not seeded during normal round on purpose
class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Team::factory()->createMany(15)->each(function ($team) {
            Offering::factory()->for($team)->createMany(2);
        });
    }
}
