<?php

use App\Models\Category;
use App\Models\Product;

test('a customer cannot manage categories', function () {
    actingAsUser();

    $this->postJson('/api/v1/admin/categories', ['name' => 'Skincare'])->assertForbidden();
});

test('a catalog manager can create a category with an auto-generated slug', function () {
    actingAsAdmin('catalog-manager');

    $response = $this->postJson('/api/v1/admin/categories', ['name' => 'Face Serums']);

    $response->assertCreated()->assertJsonPath('category.slug', 'face-serums');
});

test('a catalog manager can update a category', function () {
    actingAsAdmin('catalog-manager');
    $category = Category::factory()->create();

    $response = $this->patchJson("/api/v1/admin/categories/{$category->id}", ['name' => 'Updated Name']);

    $response->assertOk()->assertJsonPath('category.name', 'Updated Name');
});

test('a category cannot be made its own parent', function () {
    actingAsAdmin('catalog-manager');
    $category = Category::factory()->create();

    $this->patchJson("/api/v1/admin/categories/{$category->id}", ['parent_id' => $category->id])
        ->assertUnprocessable();
});

test('a category with products cannot be deleted', function () {
    actingAsAdmin('catalog-manager');
    $category = Category::factory()->create();
    Product::factory()->for($category)->create();

    $this->deleteJson("/api/v1/admin/categories/{$category->id}")->assertUnprocessable();
});

test('an empty category can be deleted', function () {
    actingAsAdmin('catalog-manager');
    $category = Category::factory()->create();

    $this->deleteJson("/api/v1/admin/categories/{$category->id}")->assertOk();
    expect(Category::find($category->id))->toBeNull();
});
