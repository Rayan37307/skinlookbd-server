<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Label;
use App\Models\Product;
use App\Models\SkinType;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leafCategories = Category::whereNotNull('parent_id')->get();
        $skinTypes = SkinType::all();
        $tags = Tag::all();
        $labels = Label::all();

        $leafCategories->each(function (Category $category) use ($skinTypes, $tags, $labels) {
            Product::factory()
                ->count(4)
                ->for($category)
                ->create()
                ->each(function (Product $product) use ($skinTypes, $tags, $labels) {
                    $product->images()->createMany(
                        collect(range(1, 2))->map(fn () => [
                            'path' => 'products/'.fake()->uuid().'.jpg',
                            'alt' => $product->name,
                            'sort_order' => 0,
                        ])->all()
                    );

                    $product->variants()->createMany(
                        collect(['30ml', '50ml', '100ml'])->random(rand(1, 3))->map(fn (string $size) => [
                            'sku' => strtoupper('SKU-'.$product->id.'-'.$size),
                            'size_label' => $size,
                            'price_override' => null,
                            'stock_quantity' => fake()->numberBetween(0, 200),
                        ])->all()
                    );

                    $product->skinTypes()->attach(
                        $skinTypes->random(rand(1, 3))->pluck('id')
                    );

                    $product->tags()->attach(
                        $tags->random(rand(0, 3))->pluck('id')
                    );

                    $product->labels()->attach(
                        $labels->random(rand(0, 2))->pluck('id')
                    );
                });
        });
    }
}
