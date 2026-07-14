<?php

use App\Models\ProductVariant;

test('an order manager cannot view the low-stock report', function () {
    actingAsAdmin('order-manager');

    $this->getJson('/api/v1/admin/inventory/low-stock')->assertForbidden();
});

test('it lists variants at or below the threshold, lowest first', function () {
    actingAsAdmin('catalog-manager');

    ProductVariant::factory()->create(['stock_quantity' => 3]);
    ProductVariant::factory()->create(['stock_quantity' => 1]);
    ProductVariant::factory()->create(['stock_quantity' => 100]);

    $response = $this->getJson('/api/v1/admin/inventory/low-stock?threshold=5');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
    expect($response->json('variants.0.stock_quantity'))->toBe(1);
});
