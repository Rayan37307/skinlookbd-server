<?php

use App\Models\User;

function actingAsFilamentAdmin(string $role = 'super-admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('a customer cannot access the admin panel', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)->get('/admin')->assertForbidden();
});

test('a super admin can view the categories and products pages', function () {
    actingAsFilamentAdmin();

    $this->get('/admin/categories')->assertOk();
    $this->get('/admin/products')->assertOk();
});

test('a super admin can view an order and its items', function () {
    actingAsFilamentAdmin();
    $order = \App\Models\Order::factory()->create();
    \App\Models\OrderItem::factory()->for($order)->create();

    $this->get('/admin/orders')->assertOk();
    $this->get("/admin/orders/{$order->id}")->assertOk();
});

test('a super admin can view coupons, banners, and reviews pages', function () {
    actingAsFilamentAdmin();
    $coupon = \App\Models\Coupon::factory()->create();
    $banner = \App\Models\Banner::factory()->create();
    $review = \App\Models\Review::factory()->create();

    $this->get('/admin/coupons')->assertOk();
    $this->get("/admin/coupons/{$coupon->id}")->assertOk();

    $this->get('/admin/banners')->assertOk();
    $this->get("/admin/banners/{$banner->id}")->assertOk();

    $this->get('/admin/reviews')->assertOk();
    $this->get("/admin/reviews/{$review->id}")->assertOk();
});

test('a super admin can manage staff', function () {
    actingAsFilamentAdmin();
    $staff = User::factory()->create();
    $staff->assignRole('catalog-manager');

    $this->get('/admin/staff')->assertOk();
    $this->get('/admin/staff/create')->assertOk();
    $this->get("/admin/staff/{$staff->id}")->assertOk();
    $this->get("/admin/staff/{$staff->id}/edit")->assertOk();
});

test('an order manager cannot manage staff', function () {
    actingAsFilamentAdmin('order-manager');

    $this->get('/admin/staff')->assertForbidden();
});

test('a customer does not appear in the staff list', function () {
    $admin = actingAsFilamentAdmin();
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->get("/admin/staff/{$customer->id}")->assertNotFound();
});

test('the dashboard renders with its widgets', function () {
    actingAsFilamentAdmin();
    \App\Models\Order::factory()->create(['status' => 'delivered', 'total' => 500]);
    \App\Models\ProductVariant::factory()->create(['stock_quantity' => 1]);

    $this->get('/admin')->assertOk();
});
