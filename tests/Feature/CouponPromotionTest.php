<?php

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;

/**
 * Adds a single variant to the current user's cart.
 */
function addPromotionProductToCart(int $price = 1000): ProductVariant
{
    $variant = ProductVariant::factory()->create([
        'price_override' => $price,
        'stock_quantity' => 10,
    ]);

    test()->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    return $variant;
}

test('a free delivery coupon waives the shipping charge at checkout', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create(['city' => 'Dhaka']);
    addPromotionProductToCart(1000);

    Coupon::factory()->freeDelivery()->create(['code' => 'FREESHIP']);

    $response = $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
        'coupon_code' => 'freeship',
    ]);

    $response->assertCreated()
        ->assertJsonPath('order.discount_total', 0)
        ->assertJsonPath('order.shipping_charge', 0)
        ->assertJsonPath('order.total', 1000);
});

test('a free delivery coupon is reported by cart validation', function () {
    actingAsUser();
    addPromotionProductToCart(1000);

    Coupon::factory()->freeDelivery()->create(['code' => 'FREESHIP']);

    $this->postJson('/api/v1/coupons/validate', ['code' => 'FREESHIP'])
        ->assertOk()
        ->assertJsonPath('free_delivery', true)
        ->assertJsonPath('discount', 0);
});

test('a maximum discount caps a percentage coupon', function () {
    actingAsUser();
    addPromotionProductToCart(1000);

    Coupon::factory()->create([
        'code' => 'HALF',
        'discount_type' => 'percent',
        'discount_value' => 50,
        'max_discount' => 200,
    ]);

    $this->postJson('/api/v1/coupons/validate', ['code' => 'HALF'])
        ->assertOk()
        ->assertJsonPath('discount', 200);
});

test('a first order only coupon is rejected for a returning customer', function () {
    $user = actingAsUser();
    Order::factory()->for($user)->create();
    addPromotionProductToCart(1000);

    Coupon::factory()->create([
        'code' => 'WELCOME',
        'first_order_only' => true,
    ]);

    $this->postJson('/api/v1/coupons/validate', ['code' => 'WELCOME'])->assertUnprocessable();
});

test('a first order only coupon is accepted for a customer without orders', function () {
    actingAsUser();
    addPromotionProductToCart(1000);

    Coupon::factory()->create([
        'code' => 'WELCOME',
        'first_order_only' => true,
    ]);

    $this->postJson('/api/v1/coupons/validate', ['code' => 'WELCOME'])
        ->assertOk()
        ->assertJsonPath('discount', 100);
});

test('a first order only coupon cannot be used by a guest', function () {
    $variant = ProductVariant::factory()->create([
        'price_override' => 1000,
        'stock_quantity' => 10,
    ]);

    $token = $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ])->headers->get('X-Cart-Token');

    Coupon::factory()->create([
        'code' => 'WELCOME',
        'first_order_only' => true,
    ]);

    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/coupons/validate', ['code' => 'WELCOME'])
        ->assertUnprocessable();
});

test('an automatic promotion applies at checkout without a code', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create(['city' => 'Dhaka']);
    addPromotionProductToCart(1000);

    $coupon = Coupon::factory()->automatic()->create([
        'discount_type' => 'flat',
        'discount_value' => 150,
    ]);

    $response = $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
    ]);

    $response->assertCreated()
        ->assertJsonPath('order.discount_total', 150);

    expect(Order::firstOrFail()->coupon_id)->toBe($coupon->id);
    expect($coupon->usages()->count())->toBe(1);
});

test('an automatic promotion stacks with a code coupon when both allow combination', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create(['city' => 'Dhaka']);
    addPromotionProductToCart(1000);

    $codeCoupon = Coupon::factory()->create([
        'code' => 'SAVE10',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'allow_combination' => true,
    ]);

    Coupon::factory()->automatic()->create([
        'discount_type' => 'flat',
        'discount_value' => 200,
        'allow_combination' => true,
    ]);

    $response = $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
        'coupon_code' => 'SAVE10',
    ]);

    $response->assertCreated()
        ->assertJsonPath('order.discount_total', 300);

    expect(Order::firstOrFail()->coupon_id)->toBe($codeCoupon->id);
    expect($codeCoupon->usages()->count())->toBe(1);
});

test('an automatic promotion does not stack when combination is not allowed', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create(['city' => 'Dhaka']);
    addPromotionProductToCart(1000);

    Coupon::factory()->create([
        'code' => 'SAVE10',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'allow_combination' => true,
    ]);

    Coupon::factory()->automatic()->create([
        'discount_type' => 'flat',
        'discount_value' => 200,
        'allow_combination' => false,
    ]);

    $response = $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
        'coupon_code' => 'SAVE10',
    ]);

    $response->assertCreated()
        ->assertJsonPath('order.discount_total', 100);
});

test('a free delivery automatic promotion also waives shipping', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create(['city' => 'Dhaka']);
    addPromotionProductToCart(1000);

    Coupon::factory()->automatic()->freeDelivery()->create();

    $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
    ])
        ->assertCreated()
        ->assertJsonPath('order.shipping_charge', 0);
});
