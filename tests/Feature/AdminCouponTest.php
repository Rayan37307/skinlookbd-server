<?php

use App\Models\Coupon;

test('a customer cannot manage coupons', function () {
    actingAsUser();

    $this->postJson('/api/v1/admin/coupons', [])->assertForbidden();
});

test('a catalog manager can create a coupon with an uppercased code', function () {
    actingAsAdmin('catalog-manager');

    $response = $this->postJson('/api/v1/admin/coupons', [
        'code' => 'newyear',
        'discount_type' => 'percent',
        'discount_value' => 15,
    ]);

    $response->assertCreated()->assertJsonPath('coupon.code', 'NEWYEAR');
});

test('a catalog manager can update a coupon', function () {
    actingAsAdmin('catalog-manager');
    $coupon = Coupon::factory()->create(['is_active' => true]);

    $response = $this->patchJson("/api/v1/admin/coupons/{$coupon->id}", ['is_active' => false]);

    $response->assertOk()->assertJsonPath('coupon.is_active', false);
});

test('a catalog manager can delete a coupon', function () {
    actingAsAdmin('catalog-manager');
    $coupon = Coupon::factory()->create();

    $this->deleteJson("/api/v1/admin/coupons/{$coupon->id}")->assertOk();
    expect(Coupon::find($coupon->id))->toBeNull();
});

test('a coupon list can be retrieved', function () {
    actingAsAdmin('catalog-manager');
    Coupon::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/admin/coupons');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(3);
});
