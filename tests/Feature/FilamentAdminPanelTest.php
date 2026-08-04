<?php

use App\Models\Address;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Tag;
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
    $order = Order::factory()->create();
    OrderItem::factory()->for($order)->create();

    $this->get('/admin/orders')->assertOk();
    $this->get("/admin/orders/{$order->id}")->assertOk();
});

test('a super admin can view coupons, banners, and reviews pages', function () {
    actingAsFilamentAdmin();
    $coupon = Coupon::factory()->create();
    $banner = Banner::factory()->create();
    $review = Review::factory()->create();

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

test('a super admin can view the customers list and both a registered and a guest customer', function () {
    actingAsFilamentAdmin();

    $customer = User::factory()->create();
    $customer->assignRole('customer');
    Address::factory()->for($customer)->create();
    Order::factory()->create(['user_id' => $customer->id, 'address_id' => null]);

    Order::factory()->create(['user_id' => null, 'address_id' => null, 'recipient_phone' => '01799999999']);

    $this->get('/admin/customers')->assertOk();

    $registered = Customer::where('type', 'registered')->where('user_id', $customer->id)->firstOrFail();
    $guest = Customer::where('type', 'guest')->where('phone', '01799999999')->firstOrFail();

    $this->get("/admin/customers/{$registered->id}")->assertOk();
    $this->get("/admin/customers/{$guest->id}")->assertOk();
});

test('an order manager can view customers but a catalog manager cannot', function () {
    actingAsFilamentAdmin('order-manager');
    $this->get('/admin/customers')->assertOk();

    actingAsFilamentAdmin('catalog-manager');
    $this->get('/admin/customers')->assertForbidden();
});

test('a super admin can manage concerns linked to a category or a tag', function () {
    actingAsFilamentAdmin();
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();
    $concernByCategory = Concern::factory()->create(['category_id' => $category->id]);
    $concernByTag = Concern::factory()->create(['tag_id' => $tag->id]);

    $this->get('/admin/concerns')->assertOk();
    $this->get('/admin/concerns/create')->assertOk();
    $this->get("/admin/concerns/{$concernByCategory->id}")->assertOk();
    $this->get("/admin/concerns/{$concernByCategory->id}/edit")->assertOk();
    $this->get("/admin/concerns/{$concernByTag->id}/edit")->assertOk();
});

test('a super admin can manage menu items', function () {
    actingAsFilamentAdmin();
    $menu = Menu::factory()->create();

    $this->get('/admin/menus')->assertOk();
    $this->get('/admin/menus/create')->assertOk();
    $this->get("/admin/menus/{$menu->id}")->assertOk();
    $this->get("/admin/menus/{$menu->id}/edit")->assertOk();
});

test('a top-level menu item with no label does not break the menus admin pages', function () {
    actingAsFilamentAdmin();
    $menu = Menu::factory()->create(['label' => null, 'parent_id' => null]);

    $this->get('/admin/menus')->assertOk();
    $this->get('/admin/menus/create')->assertOk();
    $this->get("/admin/menus/{$menu->id}")->assertOk();
    $this->get("/admin/menus/{$menu->id}/edit")->assertOk();
});

test('the dashboard renders with its widgets', function () {
    actingAsFilamentAdmin();
    Order::factory()->create(['status' => 'delivered', 'total' => 500]);
    ProductVariant::factory()->create(['stock_quantity' => 1]);

    $this->get('/admin')->assertOk();
});
