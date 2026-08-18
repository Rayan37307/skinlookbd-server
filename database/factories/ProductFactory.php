<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'brand_id' => Brand::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'sku' => strtoupper(Str::random(8)),
            'short_description' => fake()->sentence(12),
            'description' => fake()->paragraph(),
            'ingredients' => fake()->sentence(10),
            'base_price' => fake()->numberBetween(250, 3500),
            'track_inventory' => true,
            'stock_quantity' => fake()->numberBetween(0, 200),
            'free_shipping' => fake()->boolean(20),
            'meta_title' => ucwords($name),
            'meta_description' => fake()->sentence(15),
            'status' => 'active',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'draft']);
    }

    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_price' => (int) round($attributes['base_price'] * 0.8),
        ]);
    }
}
