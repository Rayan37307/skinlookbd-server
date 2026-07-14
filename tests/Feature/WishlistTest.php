<?php

use App\Models\Product;

test('guests cannot access the wishlist', function () {
    $this->getJson('/api/v1/wishlist')->assertUnauthorized();
});

test('a user can add a product to their wishlist', function () {
    actingAsUser();
    $product = Product::factory()->create();

    $response = $this->postJson('/api/v1/wishlist', ['product_id' => $product->id]);

    $response->assertCreated()->assertJsonPath('wishlist.product.id', $product->id);
});

test('a user cannot add the same product twice', function () {
    actingAsUser();
    $product = Product::factory()->create();

    $this->postJson('/api/v1/wishlist', ['product_id' => $product->id]);
    $this->postJson('/api/v1/wishlist', ['product_id' => $product->id])->assertUnprocessable();
});

test('a user can list their wishlist', function () {
    actingAsUser();
    Product::factory()->count(2)->create()->each(
        fn ($product) => $this->postJson('/api/v1/wishlist', ['product_id' => $product->id])
    );

    $response = $this->getJson('/api/v1/wishlist');

    $response->assertOk();
    expect($response->json('wishlist'))->toHaveCount(2);
});

test('a user can remove a product from their wishlist', function () {
    actingAsUser();
    $product = Product::factory()->create();
    $this->postJson('/api/v1/wishlist', ['product_id' => $product->id]);

    $this->deleteJson("/api/v1/wishlist/{$product->id}")->assertOk();

    $response = $this->getJson('/api/v1/wishlist');
    expect($response->json('wishlist'))->toHaveCount(0);
});
