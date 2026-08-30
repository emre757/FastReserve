<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\Offering;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offering>
 */
class OfferingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->name(),
            'description' => $this->faker->optional()->text(),
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeeks(2),
            'capacity' => $this->faker->numberBetween(1, 100),
            'price' => $this->faker->randomFloat(2, 0, 999),
            'currency' => $this->faker->randomElement(Currency::cases())->value,
            'booking_deadline_at' => now()->addDays(2),
            'cancellation_deadline_at' => now()->addDays(2),
        ];
    }
}
