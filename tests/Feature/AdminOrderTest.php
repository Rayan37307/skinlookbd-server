<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;

test('a catalog manager cannot manage orders', function () {
    actingAsAdmin('catalog-manager');
    $order = Order::factory()->create();

    $this->getJson('/api/v1/admin/orders')->assertForbidden();
    $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'confirmed'])->assertForbidden();
});

test('an order manager can filter orders by status', function () {
    actingAsAdmin('order-manager');
    Order::factory()->create(['status' => 'pending']);
    Order::factory()->create(['status' => 'delivered']);

    $response = $this->getJson('/api/v1/admin/orders?status=pending');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

test('an order can only move to an allowed next status', function () {
    actingAsAdmin('order-manager');
    $order = Order::factory()->create(['status' => 'pending']);

    $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'shipped'])
        ->assertUnprocessable();

    $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'confirmed'])
        ->assertOk()->assertJsonPath('order.status', 'confirmed');
});

test('cancelling an order via status update restocks variants', function () {
    actingAsAdmin('order-manager');
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $order = Order::factory()->create(['status' => 'pending']);
    OrderItem::factory()->for($order)->create(['product_variant_id' => $variant->id, 'quantity' => 2]);

    $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'cancelled'])->assertOk();

    expect($variant->fresh()->stock_quantity)->toBe(7);
});

test('marking a cod order delivered marks its payment paid', function () {
    actingAsAdmin('order-manager');
    $order = Order::factory()->create(['status' => 'processing', 'payment_method' => 'cod']);
    Payment::factory()->for($order)->create(['method' => 'cod', 'status' => 'confirmed']);
    $order->update(['status' => 'shipped']);

    $response = $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'delivered']);

    $response->assertOk();
    expect($order->fresh()->payment->status)->toBe('paid');
});

test('refunding requires a confirmed or paid payment', function () {
    actingAsAdmin('order-manager');
    $order = Order::factory()->create(['status' => 'delivered']);
    Payment::factory()->for($order)->create(['status' => 'pending']);

    $this->postJson("/api/v1/admin/orders/{$order->id}/refund")->assertUnprocessable();
});

test('a paid order can be refunded and restocks variants', function () {
    actingAsAdmin('order-manager');
    $variant = ProductVariant::factory()->create(['stock_quantity' => 3]);
    $order = Order::factory()->create(['status' => 'delivered']);
    Payment::factory()->for($order)->create(['status' => 'paid']);
    OrderItem::factory()->for($order)->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

    $response = $this->postJson("/api/v1/admin/orders/{$order->id}/refund", ['reason' => 'Damaged item']);

    $response->assertOk()->assertJsonPath('order.status', 'returned');
    expect($order->fresh()->payment->status)->toBe('refunded');
    expect($variant->fresh()->stock_quantity)->toBe(4);
});
