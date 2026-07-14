<?php

use App\Models\Product;
use App\Models\Review;

test('it lists only approved reviews for a product', function () {
    $product = Product::factory()->create();

    Review::factory()->for($product)->count(2)->create(['status' => 'approved']);
    Review::factory()->for($product)->pending()->create();
    Review::factory()->for($product)->create(['status' => 'rejected']);

    $response = $this->getJson("/api/v1/products/{$product->id}/reviews");

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});
