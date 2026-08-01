<?php

namespace Database\Factories;

use App\Models\Concern;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Concern>
 */
class ConcernFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Anti Aging', 'Acne Care', 'Oily Skin', 'Dry Skin', 'Dark Spots', 'Sensitive Skin',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'color_from' => fake()->hexColor(),
            'color_to' => fake()->hexColor(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
