<?php

namespace Database\Factories;

use App\Models\Label;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Label>
 */
class LabelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Hot', 'Sale', 'New', 'Limited', 'Exclusive']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => fake()->randomElement(['danger', 'warning', 'success', 'gray', 'primary']),
            'icon' => null,
        ];
    }
}
