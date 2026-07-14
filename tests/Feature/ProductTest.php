<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SkinType;

test('it only lists active products', function () {
    Product::factory()->count(2)->create(['status' => 'active']);
    Product::factory()->draft()->create();

    $response = $this->getJson('/api/v1/products');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

test('it filters products by category including its children', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $other = Category::factory()->create();

    Product::factory()->for($parent)->create();
    Product::factory()->for($child)->create();
    Product::factory()->for($other)->create();

    $response = $this->getJson("/api/v1/products?category={$parent->slug}");

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

test('it filters products by skin type', function () {
    $oily = SkinType::factory()->create(['name' => 'Oily', 'slug' => 'oily']);
    $dry = SkinType::factory()->create(['name' => 'Dry', 'slug' => 'dry']);

    $matching = Product::factory()->create();
    $matching->skinTypes()->attach($oily);

    $nonMatching = Product::factory()->create();
    $nonMatching->skinTypes()->attach($dry);

    $response = $this->getJson('/api/v1/products?skin_type=oily');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

test('it filters products by price range', function () {
    Product::factory()->create(['base_price' => 100]);
    Product::factory()->create(['base_price' => 500]);
    Product::factory()->create(['base_price' => 1000]);

    $response = $this->getJson('/api/v1/products?min_price=200&max_price=700');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

test('it filters products that are in stock', function () {
    $inStock = Product::factory()->create();
    ProductVariant::factory()->for($inStock)->create(['stock_quantity' => 5]);

    $outOfStock = Product::factory()->create();
    ProductVariant::factory()->for($outOfStock)->create(['stock_quantity' => 0]);

    $response = $this->getJson('/api/v1/products?in_stock=1');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

test('it searches products by name or brand', function () {
    Product::factory()->create(['name' => 'Vitamin C Serum', 'brand' => 'Glowlab']);
    Product::factory()->create(['name' => 'Hydrating Toner', 'brand' => 'Vitamin Co']);
    Product::factory()->create(['name' => 'Clay Mask', 'brand' => 'Mudworks']);

    $response = $this->getJson('/api/v1/products?search=vitamin');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

test('it sorts products by price', function () {
    Product::factory()->create(['name' => 'Cheap', 'base_price' => 100]);
    Product::factory()->create(['name' => 'Expensive', 'base_price' => 900]);

    $response = $this->getJson('/api/v1/products?sort=price_asc');

    $response->assertOk();
    expect($response->json('products.0.name'))->toBe('Cheap');
});

test('it shows a single active product by slug with full details', function () {
    $product = Product::factory()->create(['slug' => 'my-product']);
    ProductVariant::factory()->for($product)->create();

    $response = $this->getJson('/api/v1/products/my-product');

    $response->assertOk()->assertJsonPath('product.slug', 'my-product')
        ->assertJsonStructure(['product' => ['variants', 'images', 'skin_types']]);
});

test('a draft product is not publicly visible', function () {
    $product = Product::factory()->draft()->create(['slug' => 'hidden-product']);

    $this->getJson('/api/v1/products/hidden-product')->assertNotFound();
});
