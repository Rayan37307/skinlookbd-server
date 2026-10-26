<?php

use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tag;

/**
 * Creates a product with a single variant priced at `$price`.
 */
function scopedCouponProduct(int $price, array $attributes = []): Product
{
    $product = Product::factory()->create($attributes);
    ProductVariant::factory()->for($product)->create([
        'price_override' => $price,
        'stock_quantity' => 10,
    ]);

    return $product;
}

function addScopedCouponProductToCart(Product $product, int $quantity = 1): void
{
    test()->postJson('/api/v1/cart/items', [
        'product_variant_id' => $product->variants()->first()->id,
        'quantity' => $quantity,
    ]);
}

test('a product restricted coupon discounts only the selected product', function () {
    actingAsUser();

    $eligible = scopedCouponProduct(1000);
    $other = scopedCouponProduct(500);

    $coupon = Coupon::factory()
        ->forProducts($eligible)
        ->create(['code' => 'ONEPROD', 'discount_type' => 'percent', 'discount_value' => 10]);

    addScopedCouponProductToCart($eligible);
    addScopedCouponProductToCart($other);

    $response = $this->postJson('/api/v1/coupons/validate', ['code' => $coupon->code]);

    $response->assertOk();
    expect($response->json('subtotal'))->toBe(1500);
    expect($response->json('eligible_subtotal'))->toBe(1000);
    expect($response->json('discount'))->toBe(100);
    expect($response->json('total'))->toBe(1400);
});

test('a restricted coupon is rejected when the cart has no matching product', function () {
    actingAsUser();

    $coupon = Coupon::factory()
        ->forProducts(scopedCouponProduct(1000))
        ->create(['code' => 'ONEPROD']);

    addScopedCouponProductToCart(scopedCouponProduct(500));

    $this->postJson('/api/v1/coupons/validate', ['code' => $coupon->code])->assertUnprocessable();
});

test('a category restricted coupon matches products in that category', function () {
    actingAsUser();

    $category = Category::factory()->create();
    $eligible = scopedCouponProduct(1000);
    $eligible->categories()->attach($category);
    $other = scopedCouponProduct(500);

    $coupon = Coupon::factory()
        ->forCategories($category)
        ->create(['code' => 'CATEGORY', 'discount_type' => 'percent', 'discount_value' => 20]);

    addScopedCouponProductToCart($eligible);
    addScopedCouponProductToCart($other);

    $response = $this->postJson('/api/v1/coupons/validate', ['code' => $coupon->code]);

    $response->assertOk();
    expect($response->json('eligible_subtotal'))->toBe(1000);
    expect($response->json('discount'))->toBe(200);
});

test('a brand restricted coupon matches products of that brand', function () {
    actingAsUser();

    $brand = Brand::factory()->create();
    $eligible = scopedCouponProduct(1000, ['brand_id' => $brand->id]);
    $other = scopedCouponProduct(500);

    $coupon = Coupon::factory()
        ->forBrands($brand)
        ->create(['code' => 'BRAND', 'discount_type' => 'flat', 'discount_value' => 300]);

    addScopedCouponProductToCart($eligible);
    addScopedCouponProductToCart($other);

    $response = $this->postJson('/api/v1/coupons/validate', ['code' => $coupon->code]);

    $response->assertOk();
    expect($response->json('eligible_subtotal'))->toBe(1000);
    expect($response->json('discount'))->toBe(300);
});

test('a tag restricted coupon matches products carrying that tag', function () {
    actingAsUser();

    $tag = Tag::factory()->create();
    $eligible = scopedCouponProduct(1000);
    $eligible->tags()->attach($tag);
    $other = scopedCouponProduct(500);

    $coupon = Coupon::factory()
        ->forTags($tag)
        ->create(['code' => 'TAGGED', 'discount_type' => 'percent', 'discount_value' => 10]);

    addScopedCouponProductToCart($eligible);
    addScopedCouponProductToCart($other);

    $response = $this->postJson('/api/v1/coupons/validate', ['code' => $coupon->code]);

    $response->assertOk();
    expect($response->json('eligible_subtotal'))->toBe(1000);
    expect($response->json('discount'))->toBe(100);
});

test('a flat discount never exceeds the eligible subtotal', function () {
    actingAsUser();

    $eligible = scopedCouponProduct(200);
    $other = scopedCouponProduct(5000);

    $coupon = Coupon::factory()
        ->forProducts($eligible)
        ->create(['code' => 'BIGFLAT', 'discount_type' => 'flat', 'discount_value' => 1000]);

    addScopedCouponProductToCart($eligible);
    addScopedCouponProductToCart($other);

    $response = $this->postJson('/api/v1/coupons/validate', ['code' => $coupon->code]);

    $response->assertOk();
    expect($response->json('discount'))->toBe(200);
});

test('the minimum order value is measured against the eligible items of a restricted coupon', function () {
    actingAsUser();

    $eligible = scopedCouponProduct(400);
    $other = scopedCouponProduct(5000);

    $coupon = Coupon::factory()
        ->forProducts($eligible)
        ->create(['code' => 'MINORDER', 'min_order_value' => 1000]);

    addScopedCouponProductToCart($eligible);
    addScopedCouponProductToCart($other);

    $this->postJson('/api/v1/coupons/validate', ['code' => $coupon->code])->assertUnprocessable();
});

test('checkout applies a restricted coupon to the matching items only', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create(['city' => 'Dhaka']);

    $eligible = scopedCouponProduct(1000);
    $other = scopedCouponProduct(500);

    $coupon = Coupon::factory()
        ->forProducts($eligible)
        ->create(['code' => 'ONEPROD', 'discount_type' => 'percent', 'discount_value' => 10]);

    addScopedCouponProductToCart($eligible);
    addScopedCouponProductToCart($other);

    $response = $this->postJson('/api/v1/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cod',
        'coupon_code' => $coupon->code,
    ]);

    $response->assertCreated();
    expect($response->json('order.subtotal'))->toBe(1500);
    expect($response->json('order.discount_total'))->toBe(100);
});
