<?php

use App\Models\SiteMedia;
use Illuminate\Support\Facades\Storage;

test('it lists every known slot, even ones that have never been customized', function () {
    $response = $this->getJson('/api/v1/site-media');

    $response->assertOk();

    $keys = collect($response->json('site_media'))->pluck('key');
    expect($keys)->toEqual(collect(array_keys(config('site_media.slots'))));

    foreach ($response->json('site_media') as $slot) {
        expect($slot['image_url'])->toBeNull();
        expect($slot['is_active'])->toBeTrue();
    }
});

test('it returns the stored image url and toggle state for a customized slot', function () {
    Storage::fake('public');
    Storage::disk('public')->put('site-media/logo.png', 'fake-content');

    SiteMedia::factory()->create([
        'key' => 'logo',
        'image_path' => 'site-media/logo.png',
        'link_url' => null,
        'is_active' => true,
    ]);

    SiteMedia::factory()->create([
        'key' => 'footer_ribbon',
        'image_path' => null,
        'is_active' => false,
    ]);

    $response = $this->getJson('/api/v1/site-media');

    $response->assertOk();
    $slots = collect($response->json('site_media'))->keyBy('key');

    expect($slots['logo']['image_url'])->toContain('site-media/logo.png');
    expect($slots['footer_ribbon']['image_url'])->toBeNull();
    expect($slots['footer_ribbon']['is_active'])->toBeFalse();
});

test('an external image url is returned as-is', function () {
    SiteMedia::factory()->create([
        'key' => 'hero_banner_desktop',
        'image_path' => 'https://cdn.example.com/hero.jpg',
    ]);

    $response = $this->getJson('/api/v1/site-media');

    $slots = collect($response->json('site_media'))->keyBy('key');
    expect($slots['hero_banner_desktop']['image_url'])->toBe('https://cdn.example.com/hero.jpg');
});
