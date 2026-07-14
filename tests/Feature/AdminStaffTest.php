<?php

use App\Models\User;

test('an order manager cannot manage staff', function () {
    actingAsAdmin('order-manager');

    $this->getJson('/api/v1/admin/staff')->assertForbidden();
});

test('a super admin can create a staff member', function () {
    actingAsAdmin();

    $response = $this->postJson('/api/v1/admin/staff', [
        'name' => 'Order Manager',
        'email' => 'om@example.com',
        'phone' => '01712345678',
        'password' => 'password123',
        'role' => 'order-manager',
    ]);

    $response->assertCreated();
    expect(User::where('email', 'om@example.com')->first()->hasRole('order-manager'))->toBeTrue();
});

test('staff listing excludes plain customers', function () {
    actingAsAdmin();
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $response = $this->getJson('/api/v1/admin/staff');

    $response->assertOk();
    expect(collect($response->json('staff'))->pluck('id'))->not->toContain($customer->id);
});

test('a super admin can change a staff role', function () {
    $admin = actingAsAdmin();
    $staff = User::factory()->create();
    $staff->assignRole('catalog-manager');

    $response = $this->patchJson("/api/v1/admin/staff/{$staff->id}", ['role' => 'order-manager']);

    $response->assertOk();
    expect($staff->fresh()->hasRole('order-manager'))->toBeTrue();
    expect($staff->fresh()->hasRole('catalog-manager'))->toBeFalse();
});

test('a super admin cannot delete their own staff account', function () {
    $admin = actingAsAdmin();

    $this->deleteJson("/api/v1/admin/staff/{$admin->id}")->assertUnprocessable();
});

test('a super admin can delete another staff account', function () {
    actingAsAdmin();
    $staff = User::factory()->create();
    $staff->assignRole('catalog-manager');

    $this->deleteJson("/api/v1/admin/staff/{$staff->id}")->assertOk();
    expect(User::find($staff->id))->toBeNull();
});
