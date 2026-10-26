<?php

use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

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

test('a catalog manager can restrict a coupon to products, categories, brands and tags', function () {
    actingAsAdmin('catalog-manager');
    $product = Product::factory()->create();
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();
    $tag = Tag::factory()->create();

    $response = $this->postJson('/api/v1/admin/coupons', [
        'code' => 'scoped10',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'product_ids' => [$product->id],
        'category_ids' => [$category->id],
        'brand_ids' => [$brand->id],
        'tag_ids' => [$tag->id],
    ]);

    $response->assertCreated();

    $coupon = Coupon::where('code', 'SCOPED10')->firstOrFail();

    expect($coupon->products->pluck('id')->all())->toBe([$product->id]);
    expect($coupon->categories->pluck('id')->all())->toBe([$category->id]);
    expect($coupon->brands->pluck('id')->all())->toBe([$brand->id]);
    expect($coupon->tags->pluck('id')->all())->toBe([$tag->id]);
    expect($response->json('coupon.applies_to.products.0.name'))->toBe($product->name);
});

test('an empty scope array clears the restrictions on update', function () {
    actingAsAdmin('catalog-manager');
    $product = Product::factory()->create();
    $coupon = Coupon::factory()->forProducts($product)->create();

    $this->patchJson("/api/v1/admin/coupons/{$coupon->id}", ['product_ids' => []])
        ->assertOk();

    expect($coupon->fresh()->products()->count())->toBe(0);
});

test('an unknown product id is rejected when scoping a coupon', function () {
    actingAsAdmin('catalog-manager');

    $this->postJson('/api/v1/admin/coupons', [
        'code' => 'BADPRODUCT',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'product_ids' => [999999],
    ])->assertUnprocessable();
});

test('a catalog manager can create an automatic promotion with the new promotion fields', function () {
    actingAsAdmin('catalog-manager');

    $response = $this->postJson('/api/v1/admin/coupons', [
        'name' => 'October Special',
        'method' => 'automatic',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'max_discount' => 120,
        'min_order_value' => 1400,
        'first_order_only' => true,
        'max_uses' => 100,
        'max_uses_per_user' => 1,
        'allow_combination' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('coupon.name', 'October Special')
        ->assertJsonPath('coupon.method', 'automatic')
        ->assertJsonPath('coupon.code', null)
        ->assertJsonPath('coupon.max_discount', 120)
        ->assertJsonPath('coupon.first_order_only', true)
        ->assertJsonPath('coupon.allow_combination', true);
});

test('a free delivery coupon stores a zero discount value', function () {
    actingAsAdmin('catalog-manager');

    $response = $this->postJson('/api/v1/admin/coupons', [
        'name' => 'Free delivery',
        'code' => 'FREESHIP',
        'discount_type' => 'free_delivery',
    ]);

    $response->assertCreated()
        ->assertJsonPath('coupon.discount_type', 'free_delivery')
        ->assertJsonPath('coupon.discount_value', 0);
});

test('a code is required for a code-based coupon but not for an automatic one', function () {
    actingAsAdmin('catalog-manager');

    $this->postJson('/api/v1/admin/coupons', [
        'name' => 'No code',
        'method' => 'code',
        'discount_type' => 'percent',
        'discount_value' => 10,
    ])->assertUnprocessable();
});

test('a coupon can be scoped to products and categories from the Filament form', function () {
    $admin = User::factory()->create();
    $admin->assignRole('catalog-manager');
    $this->actingAs($admin);

    Filament::setCurrentPanel('admin');

    $product = Product::factory()->create();
    $category = Category::factory()->create();

    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'name' => 'Form coupon',
            'code' => 'FORM10',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order_value' => 0,
            'applies_to' => 'items',
            'products' => [$product->id],
            'categories' => [$category->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $coupon = Coupon::where('code', 'FORM10')->firstOrFail();

    expect($coupon->products->pluck('id')->all())->toBe([$product->id]);
    expect($coupon->categories->pluck('id')->all())->toBe([$category->id]);
});

test('a free delivery automatic promotion can be created from the Filament form', function () {
    $admin = User::factory()->create();
    $admin->assignRole('catalog-manager');
    $this->actingAs($admin);

    Filament::setCurrentPanel('admin');

    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'name' => 'Free delivery',
            'method' => 'automatic',
            'discount_type' => 'free_delivery',
            'min_order_value' => 0,
            'applies_to' => 'order',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $coupon = Coupon::where('name', 'Free delivery')->firstOrFail();

    expect($coupon->method)->toBe('automatic');
    expect($coupon->code)->toBeNull();
    expect($coupon->discount_type)->toBe('free_delivery');
    expect($coupon->discount_value)->toBe(0);
});
