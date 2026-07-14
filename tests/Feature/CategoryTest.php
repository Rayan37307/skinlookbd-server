<?php

use App\Models\Category;

test('it lists only active top-level categories with their active children', function () {
    $parent = Category::factory()->create(['name' => 'Skincare']);
    Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Cleansers']);
    Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Inactive Child', 'is_active' => false]);
    Category::factory()->create(['name' => 'Inactive Parent', 'is_active' => false]);

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk();
    expect($response->json('categories'))->toHaveCount(1);
    expect($response->json('categories.0.children'))->toHaveCount(1);
    expect($response->json('categories.0.children.0.name'))->toBe('Cleansers');
});
