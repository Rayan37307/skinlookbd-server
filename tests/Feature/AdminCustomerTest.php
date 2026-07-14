<?php

use App\Models\Order;
use App\Models\User;

test('a catalog manager cannot access customers', function () {
    actingAsAdmin('catalog-manager');

    $this->getJson('/api/v1/admin/customers')->assertForbidden();
});

test('the customer list reports order count and lifetime value', function () {
    actingAsAdmin('order-manager');

    $customer = User::factory()->create();
    $customer->assignRole('customer');
    Order::factory()->for($customer)->create(['status' => 'delivered', 'total' => 1500]);
    Order::factory()->for($customer)->create(['status' => 'cancelled', 'total' => 999]);

    $response = $this->getJson('/api/v1/admin/customers');

    $response->assertOk();
    $found = collect($response->json('customers'))->firstWhere('id', $customer->id);
    expect($found['orders_count'])->toBe(2);
    expect($found['lifetime_value'])->toBe(1500);
});

test('staff accounts are not returned as customer details', function () {
    actingAsAdmin('order-manager');
    $staff = User::factory()->create();
    $staff->assignRole('catalog-manager');

    $this->getJson("/api/v1/admin/customers/{$staff->id}")->assertNotFound();
});

test('a customer detail page includes order history', function () {
    actingAsAdmin('order-manager');
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    Order::factory()->for($customer)->create(['status' => 'delivered', 'total' => 750]);

    $response = $this->getJson("/api/v1/admin/customers/{$customer->id}");

    $response->assertOk();
    expect($response->json('customer.lifetime_value'))->toBe(750);
    expect($response->json('orders'))->toHaveCount(1);
});
