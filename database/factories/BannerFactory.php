<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'image' => 'banners/'.fake()->uuid().'.jpg',
            'link_url' => fake()->url(),
            'sort_order' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }
}
