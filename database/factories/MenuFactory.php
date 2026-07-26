<?php

namespace Database\Factories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'label' => fake()->unique()->word(),
            'type' => 'custom_url',
            'target' => '/'.fake()->slug(),
            'style' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
