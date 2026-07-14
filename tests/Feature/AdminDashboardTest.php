<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;

test('a catalog manager cannot access the dashboard', function () {
    actingAsAdmin('catalog-manager');

    $this->getJson('/api/v1/admin/dashboard/summary')->assertForbidden();
});

test('the summary reports revenue, order counts, and low stock', function () {
    actingAsAdmin('order-manager');

    Order::factory()->create(['status' => 'delivered', 'total' => 1000, 'created_at' => now()]);
    Order::factory()->create(['status' => 'pending', 'total' => 500, 'created_at' => now()]);
    Order::factory()->create(['status' => 'cancelled', 'total' => 300, 'created_at' => now()]);

    ProductVariant::factory()->create(['stock_quantity' => 2]);
    ProductVariant::factory()->create(['stock_quantity' => 50]);

    $response = $this->getJson('/api/v1/admin/dashboard/summary');

    $response->assertOk();
    expect($response->json('revenue.today'))->toBe(1000);
    expect($response->json('orders_by_status.pending'))->toBe(1);
    expect($response->json('orders_by_status.delivered'))->toBe(1);
    expect($response->json('low_stock_count'))->toBe(1);
});

test('sales returns a daily time series within the requested range', function () {
    actingAsAdmin('order-manager');

    Order::factory()->create(['status' => 'delivered', 'total' => 200, 'created_at' => now()]);
    Order::factory()->create(['status' => 'delivered', 'total' => 300, 'created_at' => now()]);

    $response = $this->getJson('/api/v1/admin/dashboard/sales?from='.now()->subDay()->toDateString().'&to='.now()->toDateString());

    $response->assertOk();
    $sales = $response->json('sales');
    expect($sales)->toHaveCount(1);
    expect($sales[0]['revenue'])->toBe(500);
    expect($sales[0]['orders'])->toBe(2);
});

test('top products ranks by units sold', function () {
    actingAsAdmin('order-manager');

    $popular = Product::factory()->create(['name' => 'Popular Serum']);
    $niche = Product::factory()->create(['name' => 'Niche Toner']);

    $order = Order::factory()->create(['status' => 'delivered']);
    OrderItem::factory()->for($order)->create(['product_id' => $popular->id, 'quantity' => 10, 'line_total' => 1000]);
    OrderItem::factory()->for($order)->create(['product_id' => $niche->id, 'quantity' => 1, 'line_total' => 100]);

    $response = $this->getJson('/api/v1/admin/dashboard/top-products');

    $response->assertOk();
    expect($response->json('top_products.0.name'))->toBe('Popular Serum');
    expect($response->json('top_products.0.units_sold'))->toBe(10);
});
