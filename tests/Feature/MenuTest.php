<?php

use App\Models\Menu;

test('it lists active menu items as a nested tree', function () {
    $parent = Menu::factory()->create(['label' => 'Shop', 'sort_order' => 0]);
    Menu::factory()->create(['parent_id' => $parent->id, 'label' => 'Serums', 'sort_order' => 0]);
    Menu::factory()->create(['is_active' => false]);

    $response = $this->getJson('/api/v1/menu');

    $response->assertOk();
    expect($response->json('menu'))->toHaveCount(1);
    expect($response->json('menu.0.children'))->toHaveCount(1);
});

test('a menu item can be created without a label', function () {
    $menu = Menu::factory()->create(['label' => null]);

    $response = $this->getJson('/api/v1/menu');

    $response->assertOk();
    expect($response->json('menu.0.id'))->toBe($menu->id);
    expect($response->json('menu.0.label'))->toBeNull();
});
