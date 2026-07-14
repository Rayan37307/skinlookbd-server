<?php

use App\Models\Order;

test('a catalog manager cannot export reports', function () {
    actingAsAdmin('catalog-manager');

    $this->get('/api/v1/admin/reports/export')->assertForbidden();
});

test('an order manager can export orders as csv', function () {
    actingAsAdmin('order-manager');
    $order = Order::factory()->create(['order_number' => 'ORD-TEST-001']);

    $response = $this->get('/api/v1/admin/reports/export');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->streamedContent())->toContain('ORD-TEST-001');
});
