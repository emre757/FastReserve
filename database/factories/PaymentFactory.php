<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_checkout_id' => 'cs_test',
            'status' => PaymentStatus::Pending,
            'amount' => 1055, // 10.55
            'currency' => 'USD',
        ];
    }
}
