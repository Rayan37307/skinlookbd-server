<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;

test('a user can list only their own orders', function () {
    $user = actingAsUser();
    Order::factory()->for($user)->create();
    Order::factory()->create();

    $response = $this->getJson('/api/v1/orders');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

test("a user cannot view another user's order", function () {
    actingAsUser();
    $order = Order::factory()->create();

    $this->getJson("/api/v1/orders/{$order->id}")->assertNotFound();
});

test('a user can cancel a pending order and stock is restored', function () {
    $user = actingAsUser();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $order = Order::factory()->for($user)->create(['status' => 'pending']);
    OrderItem::factory()->for($order)->create(['product_variant_id' => $variant->id, 'quantity' => 3]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/cancel");

    $response->assertOk()->assertJsonPath('order.status', 'cancelled');
    expect($variant->fresh()->stock_quantity)->toBe(8);
});

test('a shipped order cannot be cancelled', function () {
    $user = actingAsUser();
    $order = Order::factory()->for($user)->create(['status' => 'shipped']);

    $this->postJson("/api/v1/orders/{$order->id}/cancel")->assertUnprocessable();
});

test('an order manager can confirm a cod order', function () {
    $manager = User::factory()->create();
    $manager->assignRole('order-manager');
    test()->actingAs($manager, 'sanctum');

    $order = Order::factory()->create(['payment_method' => 'cod', 'status' => 'pending']);
    Payment::factory()->for($order)->create(['method' => 'cod', 'status' => 'pending']);

    $response = $this->postJson('/api/v1/payments/cod/confirm', ['order_id' => $order->id]);

    $response->assertOk()->assertJsonPath('order.status', 'confirmed');
    expect($order->fresh()->payment->status)->toBe('confirmed');
});

test('a regular customer cannot confirm a cod order', function () {
    actingAsUser();
    $order = Order::factory()->create(['payment_method' => 'cod', 'status' => 'pending']);

    $this->postJson('/api/v1/payments/cod/confirm', ['order_id' => $order->id])->assertForbidden();
});
