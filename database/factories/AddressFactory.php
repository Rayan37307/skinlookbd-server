<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Home', 'Office']),
            'recipient_name' => fake()->name(),
            'phone' => '01'.fake()->numerify('#########'),
            'email' => fake()->boolean() ? fake()->safeEmail() : null,
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'city' => 'Dhaka',
            'postal_code' => fake()->postcode(),
            'type' => 'shipping',
            'is_default' => false,
        ];
    }
}
