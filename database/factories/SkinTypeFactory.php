<?php

namespace Database\Factories;

use App\Models\SkinType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SkinType>
 */
class SkinTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Oily', 'Dry', 'Combination', 'Sensitive', 'Normal', 'Acne-Prone', 'Mature']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
