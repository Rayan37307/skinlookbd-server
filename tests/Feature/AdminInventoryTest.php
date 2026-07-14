<?php

use App\Models\InventoryLog;
use App\Models\ProductVariant;

test('a catalog manager can restock a variant', function () {
    actingAsAdmin('catalog-manager');
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);

    $response = $this->postJson('/api/v1/admin/inventory/adjustments', [
        'product_variant_id' => $variant->id,
        'change_quantity' => 10,
        'reason' => 'restock',
    ]);

    $response->assertOk()->assertJsonPath('variant.stock_quantity', 15);
    expect(InventoryLog::where('product_variant_id', $variant->id)->count())->toBe(1);
});

test('an adjustment cannot push stock below zero', function () {
    actingAsAdmin('catalog-manager');
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);

    $this->postJson('/api/v1/admin/inventory/adjustments', [
        'product_variant_id' => $variant->id,
        'change_quantity' => -10,
        'reason' => 'adjustment',
    ])->assertUnprocessable();

    expect($variant->fresh()->stock_quantity)->toBe(5);
});

test('a customer cannot adjust inventory', function () {
    actingAsUser();
    $variant = ProductVariant::factory()->create();

    $this->postJson('/api/v1/admin/inventory/adjustments', [
        'product_variant_id' => $variant->id,
        'change_quantity' => 5,
        'reason' => 'restock',
    ])->assertForbidden();
});
