<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;

test('guests cannot submit a review', function () {
    $product = Product::factory()->create();

    $this->postJson("/api/v1/products/{$product->id}/reviews", ['rating' => 5])->assertUnauthorized();
});

test('a user who purchased the product can submit a review', function () {
    $user = actingAsUser();
    $product = Product::factory()->create();
    $order = Order::factory()->for($user)->create(['status' => 'delivered']);
    OrderItem::factory()->for($order)->create(['product_id' => $product->id]);

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
        'rating' => 5,
        'title' => 'Great product',
        'body' => 'Worked really well for my skin.',
    ]);

    $response->assertCreated();
    expect(Review::where('product_id', $product->id)->where('user_id', $user->id)->first()->status)->toBe('pending');
});

test('a user who has not purchased the product cannot review it', function () {
    actingAsUser();
    $product = Product::factory()->create();

    $this->postJson("/api/v1/products/{$product->id}/reviews", ['rating' => 4])->assertUnprocessable();
});

test('a user cannot review the same product twice', function () {
    $user = actingAsUser();
    $product = Product::factory()->create();
    $order = Order::factory()->for($user)->create(['status' => 'delivered']);
    OrderItem::factory()->for($order)->create(['product_id' => $product->id]);

    $this->postJson("/api/v1/products/{$product->id}/reviews", ['rating' => 5]);
    $this->postJson("/api/v1/products/{$product->id}/reviews", ['rating' => 3])
        ->assertUnprocessable();
});

test('a cancelled order does not count as a verified purchase', function () {
    $user = actingAsUser();
    $product = Product::factory()->create();
    $order = Order::factory()->for($user)->create(['status' => 'cancelled']);
    OrderItem::factory()->for($order)->create(['product_id' => $product->id]);

    $this->postJson("/api/v1/products/{$product->id}/reviews", ['rating' => 5])->assertUnprocessable();
});
