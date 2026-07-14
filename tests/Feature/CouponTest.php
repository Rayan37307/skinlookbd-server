<?php

use App\Models\Coupon;
use App\Models\ProductVariant;

test('guests cannot validate a coupon', function () {
    $this->postJson('/api/v1/coupons/validate', ['code' => 'ANY'])->assertUnauthorized();
});

test('a valid coupon returns the computed discount', function () {
    actingAsUser();
    $variant = ProductVariant::factory()->create(['price_override' => 1000, 'stock_quantity' => 10]);
    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    Coupon::factory()->create(['code' => 'SAVE20', 'discount_type' => 'percent', 'discount_value' => 20]);

    $response = $this->postJson('/api/v1/coupons/validate', ['code' => 'save20']);

    $response->assertOk();
    expect($response->json('discount'))->toBe(200);
    expect($response->json('total'))->toBe(800);
});

test('an unknown coupon code is rejected', function () {
    actingAsUser();

    $this->postJson('/api/v1/coupons/validate', ['code' => 'NOPE'])->assertUnprocessable();
});

test('a coupon below its minimum order value is rejected', function () {
    actingAsUser();
    $variant = ProductVariant::factory()->create(['price_override' => 100, 'stock_quantity' => 10]);
    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    Coupon::factory()->create(['code' => 'BIGORDER', 'min_order_value' => 5000]);

    $this->postJson('/api/v1/coupons/validate', ['code' => 'BIGORDER'])->assertUnprocessable();
});

test('an expired coupon is rejected', function () {
    actingAsUser();
    $variant = ProductVariant::factory()->create(['price_override' => 1000, 'stock_quantity' => 10]);
    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    Coupon::factory()->create(['code' => 'EXPIRED', 'expires_at' => now()->subDay()]);

    $this->postJson('/api/v1/coupons/validate', ['code' => 'EXPIRED'])->assertUnprocessable();
});
