<?php

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SkinType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a customer cannot manage products', function () {
    actingAsUser();

    $this->postJson('/api/v1/admin/products', [])->assertForbidden();
});

test('a catalog manager can create a product with skin types', function () {
    actingAsAdmin('catalog-manager');
    $category = Category::factory()->create();
    $skinType = SkinType::factory()->create();

    $response = $this->postJson('/api/v1/admin/products', [
        'category_id' => $category->id,
        'name' => 'Niacinamide Serum',
        'base_price' => 500,
        'skin_type_ids' => [$skinType->id],
    ]);

    $response->assertCreated()
        ->assertJsonPath('product.slug', 'niacinamide-serum')
        ->assertJsonPath('product.status', 'draft');
    expect($response->json('product.skin_types'))->toContain($skinType->name);
});

test('an admin can see products of any status', function () {
    actingAsAdmin('catalog-manager');
    Product::factory()->draft()->create();

    $response = $this->getJson('/api/v1/admin/products');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

test('an admin can update a product', function () {
    actingAsAdmin('catalog-manager');
    $product = Product::factory()->create();

    $response = $this->patchJson("/api/v1/admin/products/{$product->id}", ['status' => 'active']);

    $response->assertOk()->assertJsonPath('product.status', 'active');
});

test('an admin can add and remove variants', function () {
    actingAsAdmin('catalog-manager');
    $product = Product::factory()->create();

    $storeResponse = $this->postJson("/api/v1/admin/products/{$product->id}/variants", [
        'sku' => 'TEST-SKU-1',
        'size_label' => '30ml',
        'stock_quantity' => 10,
    ]);
    $storeResponse->assertCreated();
    $variantId = $storeResponse->json('variant.id');

    $this->deleteJson("/api/v1/admin/variants/{$variantId}")->assertOk();
    expect(ProductVariant::find($variantId))->toBeNull();
});

test('an admin can upload product images', function () {
    Storage::fake('public');
    actingAsAdmin('catalog-manager');
    $product = Product::factory()->create();

    $response = $this->postJson("/api/v1/admin/products/{$product->id}/images", [
        'images' => [UploadedFile::fake()->image('front.jpg'), UploadedFile::fake()->image('back.jpg')],
    ]);

    $response->assertCreated();
    expect($product->images()->count())->toBe(2);
});

test('an admin can delete a product image', function () {
    Storage::fake('public');
    actingAsAdmin('catalog-manager');
    $product = Product::factory()->create();
    $image = $product->images()->create(['path' => 'products/test.jpg', 'sort_order' => 0]);

    $this->deleteJson("/api/v1/admin/products/{$product->id}/images/{$image->id}")->assertOk();
    expect($product->images()->count())->toBe(0);
});

test('a product with existing orders cannot be deleted', function () {
    actingAsAdmin('catalog-manager');
    $product = Product::factory()->create();
    OrderItem::factory()->create(['product_id' => $product->id]);

    $this->deleteJson("/api/v1/admin/products/{$product->id}")->assertUnprocessable();
});
