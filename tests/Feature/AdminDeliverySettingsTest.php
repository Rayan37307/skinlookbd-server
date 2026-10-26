<?php

use App\Models\User;

test('a super admin can open the delivery settings page', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $this->actingAs($user)->get('/admin/delivery-settings')->assertOk();
});

test('an order manager can open the delivery settings page', function () {
    $user = User::factory()->create();
    $user->assignRole('order-manager');

    $this->actingAs($user)->get('/admin/delivery-settings')->assertOk();
});

test('a customer cannot open the delivery settings page', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)->get('/admin/delivery-settings')->assertForbidden();
});
