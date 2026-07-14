<?php

use App\Models\Banner;

test('it only lists active, currently live banners', function () {
    Banner::factory()->create(['is_active' => true]);
    Banner::factory()->create(['is_active' => false]);
    Banner::factory()->create(['is_active' => true, 'starts_at' => now()->addDay()]);
    Banner::factory()->create(['is_active' => true, 'expires_at' => now()->subDay()]);

    $response = $this->getJson('/api/v1/banners');

    $response->assertOk();
    expect($response->json('banners'))->toHaveCount(1);
});
