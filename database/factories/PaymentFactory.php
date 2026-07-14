<?php

namespace Database\Factories;

use App\Models\Order;
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
            'order_id' => Order::factory(),
            'method' => 'cod',
            'status' => 'pending',
            'transaction_ref' => null,
            'amount' => fake()->numberBetween(500, 5000),
            'paid_at' => null,
        ];
    }
}
