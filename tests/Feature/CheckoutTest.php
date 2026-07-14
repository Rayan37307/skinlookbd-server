<?php

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;

test('guests cannot check out', function () {
    $this->postJson('/api/v1/checkout', [])->assertUnauthorized();
});

test('checkout creates an order, decrements stock, and clears the cart', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create(['city' => 'Dhaka']);
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 2]);

    $response = $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
    ]);

    $response->assertCreated();
    expect(Order::count())->toBe(1);
    expect($variant->fresh()->stock_quantity)->toBe(8);
    expect($user->fresh()->orders()->first()->items()->count())->toBe(1);

    $this->getJson('/api/v1/cart')->assertJsonCount(0, 'cart.items');
});

test('checkout fails with an empty cart', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create();

    $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
    ])->assertUnprocessable();
});

test('checkout fails when stock is insufficient', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 1]);

    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);
    $variant->update(['stock_quantity' => 0]);

    $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
    ])->assertUnprocessable();
});

test('checkout applies a valid coupon and reduces the total', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10, 'price_override' => 1000]);
    $coupon = Coupon::factory()->create(['code' => 'SAVE10', 'discount_type' => 'percent', 'discount_value' => 10]);

    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    $response = $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
        'coupon_code' => 'save10',
    ]);

    $response->assertCreated();
    expect($response->json('order.discount_total'))->toBe(100);
    expect($response->json('order.subtotal'))->toBe(1000);
});

test('checkout uses the matching shipping zone rate', function () {
    $user = actingAsUser();
    $zone = ShippingZone::factory()->create(['areas' => ['Dhaka']]);
    ShippingRate::factory()->for($zone, 'zone')->create(['charge' => 50]);

    $address = Address::factory()->for($user)->create(['city' => 'Dhaka']);
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    $response = $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
    ]);

    $response->assertCreated()->assertJsonPath('order.shipping_charge', 50);
});

test("a user cannot check out using another user's address", function () {
    actingAsUser();
    $otherAddress = Address::factory()->create();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    $this->postJson('/api/v1/checkout', [
        'address_id' => $otherAddress->id,
        'payment_method' => 'cod',
    ])->assertUnprocessable();
});
