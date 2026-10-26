<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'method' => 'code',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'max_discount' => null,
            'min_order_value' => 0,
            'first_order_only' => false,
            'max_uses' => null,
            'max_uses_per_user' => null,
            'allow_combination' => false,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    /**
     * An automatic promotion, applied at checkout without a code.
     */
    public function automatic(): static
    {
        return $this->state([
            'method' => 'automatic',
            'code' => null,
        ]);
    }

    /**
     * A coupon that waives the delivery charge.
     */
    public function freeDelivery(): static
    {
        return $this->state([
            'discount_type' => 'free_delivery',
            'discount_value' => 0,
        ]);
    }

    /**
     * Restrict the coupon to specific products.
     */
    public function forProducts(Product|Collection|array $products): static
    {
        return $this->withScope('products', $products);
    }

    /**
     * Restrict the coupon to products in specific categories.
     */
    public function forCategories(Category|Collection|array $categories): static
    {
        return $this->withScope('categories', $categories);
    }

    /**
     * Restrict the coupon to products of specific brands.
     */
    public function forBrands(Brand|Collection|array $brands): static
    {
        return $this->withScope('brands', $brands);
    }

    /**
     * Restrict the coupon to products carrying specific tags.
     */
    public function forTags(Tag|Collection|array $tags): static
    {
        return $this->withScope('tags', $tags);
    }

    /**
     * @param  Model|Collection<int, Model>|array<int, Model>  $models
     */
    private function withScope(string $relation, Model|Collection|array $models): static
    {
        $ids = collect($models instanceof Model ? [$models] : $models)->pluck('id')->all();

        return $this->afterCreating(fn (Coupon $coupon) => $coupon->{$relation}()->sync($ids));
    }
}
