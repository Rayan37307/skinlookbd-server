<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order_value' => 0,
            'max_uses' => null,
            'max_uses_per_user' => null,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }
}
