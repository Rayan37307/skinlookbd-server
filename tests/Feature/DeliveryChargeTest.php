<?php

use App\Models\Address;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\ShippingZone;
use App\Services\ShippingSettingsService;

function createDhakaDeliveryZone(int $charge = 60, ?int $freeDeliveryFrom = null): ShippingZone
{
    $zone = ShippingZone::factory()->create(['name' => 'Dhaka Metro', 'areas' => ['Dhaka']]);
    $zone->rate()->create([
        'charge' => $charge,
        'eta_days' => 2,
        'free_delivery_threshold' => $freeDeliveryFrom,
    ]);

    return $zone;
}

test('the shipping estimate uses the configured zone instead of the built-in tier', function () {
    createDhakaDeliveryZone(charge: 85);

    $this->getJson('/api/v1/shipping/estimate?city=Dhaka')
        ->assertOk()
        ->assertJson(['charge' => 85, 'eta_days' => 2, 'zone' => 'Dhaka Metro']);
});

test('cities no configured zone covers fall back to the built-in tier', function () {
    createDhakaDeliveryZone();

    $this->getJson('/api/v1/shipping/estimate?city=Sylhet')
        ->assertOk()
        ->assertJson(['charge' => 130, 'eta_days' => 5]);
});

test('the shipping estimate waives delivery at or above the free delivery threshold', function () {
    createDhakaDeliveryZone(charge: 60, freeDeliveryFrom: 2000);

    $this->getJson('/api/v1/shipping/estimate?city=Dhaka&subtotal=2000')
        ->assertOk()
        ->assertJson([
            'charge' => 0,
            'base_charge' => 60,
            'is_free_delivery' => true,
            'free_delivery_threshold' => 2000,
        ]);
});

test('the shipping estimate still charges delivery below the free delivery threshold', function () {
    createDhakaDeliveryZone(charge: 60, freeDeliveryFrom: 2000);

    $this->getJson('/api/v1/shipping/estimate?city=Dhaka&subtotal=1999')
        ->assertOk()
        ->assertJson(['charge' => 60, 'is_free_delivery' => false]);
});

test('checkout waives delivery once the cart subtotal reaches the threshold', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create(['city' => 'Dhaka']);
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10, 'price_override' => 2500]);
    createDhakaDeliveryZone(charge: 60, freeDeliveryFrom: 2000);

    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
    ])->assertCreated()->assertJsonPath('order.shipping_charge', 0);
});

test('checkout still charges delivery below the threshold', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create(['city' => 'Dhaka']);
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10, 'price_override' => 500]);
    createDhakaDeliveryZone(charge: 60, freeDeliveryFrom: 2000);

    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
    ])->assertCreated()->assertJsonPath('order.shipping_charge', 60);
});

test('the free delivery threshold measures the subtotal before the coupon discount', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create(['city' => 'Dhaka']);
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10, 'price_override' => 2000]);
    Coupon::factory()->create(['code' => 'TENOFF', 'discount_type' => 'percent', 'discount_value' => 10]);
    createDhakaDeliveryZone(charge: 60, freeDeliveryFrom: 2000);

    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
        'coupon_code' => 'TENOFF',
    ])->assertCreated()
        ->assertJsonPath('order.discount_total', 200)
        ->assertJsonPath('order.shipping_charge', 0);
});

test('the delivery settings form starts from the built-in tiers when nothing is configured', function () {
    $rows = app(ShippingSettingsService::class)->rows();

    expect($rows)->toHaveCount(3);
    expect(array_column($rows, 'name'))->toBe(['Dhaka Metro', 'Dhaka Suburb', 'Rest of Bangladesh']);
    expect($rows[0]['charge'])->toBe(70);
});

test('saving delivery settings updates existing zones, creates new ones and drops removed ones', function () {
    $settings = app(ShippingSettingsService::class);
    $existing = createDhakaDeliveryZone(charge: 60);
    $removed = ShippingZone::factory()->create(['name' => 'Old Zone', 'areas' => ['Bogura']]);

    $settings->save([
        [
            'id' => $existing->id,
            'name' => 'Dhaka',
            'areas' => ['Dhaka'],
            'charge' => 80,
            'eta_days' => 1,
            'free_delivery_threshold' => 3000,
        ],
        [
            'id' => null,
            'name' => 'Sylhet',
            'areas' => ['Sylhet'],
            'charge' => 200,
            'eta_days' => 4,
            'free_delivery_threshold' => '',
        ],
    ]);

    expect(ShippingZone::count())->toBe(2);
    expect(ShippingZone::find($removed->id))->toBeNull();

    $existing->refresh();
    expect($existing->name)->toBe('Dhaka');
    expect($existing->rate->charge)->toBe(80);
    expect($existing->rate->eta_days)->toBe(1);
    expect($existing->rate->free_delivery_threshold)->toBe(3000);

    $sylhet = ShippingZone::where('name', 'Sylhet')->first();
    expect($sylhet->rate->charge)->toBe(200);
    expect($sylhet->rate->free_delivery_threshold)->toBeNull();
});
