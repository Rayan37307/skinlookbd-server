<?php

namespace Database\Factories;

use App\Models\SiteMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteMedia>
 */
class SiteMediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'image_path' => 'site-media/'.fake()->uuid().'.jpg',
            'link_url' => null,
            'is_active' => true,
        ];
    }
}
