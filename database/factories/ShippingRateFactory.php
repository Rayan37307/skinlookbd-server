<?php

namespace Database\Factories;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingRate>
 */
class ShippingRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipping_zone_id' => ShippingZone::factory(),
            'charge' => fake()->numberBetween(60, 150),
            'eta_days' => fake()->numberBetween(1, 5),
        ];
    }
}
