<?php

namespace Database\Seeders;

use App\Models\Offering;
use App\Models\Team;
use Illuminate\Database\Seeder;

// not seeded during normal round on purpose
class OfferingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $team = Team::factory()->create();

        Offering::factory()->for($team)->createMany(10);
    }
}
