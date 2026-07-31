<?php

use App\Models\SiteMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a customer cannot manage site media', function () {
    actingAsUser();

    $this->putJson('/api/v1/admin/site-media/logo', [])->assertForbidden();
});

test('a catalog manager can upload an image for a slot', function () {
    Storage::fake('public');
    actingAsAdmin('catalog-manager');

    $response = $this->put('/api/v1/admin/site-media/footer_ribbon', [
        'image' => UploadedFile::fake()->image('ribbon.jpg'),
        'is_active' => true,
    ]);

    $response->assertOk()->assertJsonPath('site_media.key', 'footer_ribbon');

    $siteMedia = SiteMedia::where('key', 'footer_ribbon')->firstOrFail();
    Storage::disk('public')->assertExists($siteMedia->image_path);
});

test('a catalog manager can set an external image url instead of uploading a file', function () {
    actingAsAdmin('catalog-manager');

    $response = $this->putJson('/api/v1/admin/site-media/hero_banner_desktop', [
        'image_url' => 'https://cdn.example.com/hero.jpg',
        'link_url' => '/products?tag=new-arrival',
    ]);

    $response->assertOk();
    expect(SiteMedia::where('key', 'hero_banner_desktop')->firstOrFail())
        ->image_path->toBe('https://cdn.example.com/hero.jpg')
        ->link_url->toBe('/products?tag=new-arrival');
});

test('sending both an image file and an image url is rejected', function () {
    Storage::fake('public');
    actingAsAdmin('catalog-manager');

    $response = $this->put('/api/v1/admin/site-media/logo', [
        'image' => UploadedFile::fake()->image('logo.jpg'),
        'image_url' => 'https://cdn.example.com/logo.jpg',
    ]);

    $response->assertStatus(422);
});

test('a catalog manager can clear a slot back to the default image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('site-media/existing.jpg', 'fake-content');
    actingAsAdmin('catalog-manager');

    $siteMedia = SiteMedia::factory()->create([
        'key' => 'footer_ribbon',
        'image_path' => 'site-media/existing.jpg',
        'is_active' => true,
    ]);

    $response = $this->deleteJson('/api/v1/admin/site-media/footer_ribbon/image');

    $response->assertOk()->assertJsonPath('site_media.image_url', null);
    Storage::disk('public')->assertMissing('site-media/existing.jpg');
    expect($siteMedia->refresh()->is_active)->toBeTrue();
});

test('an unknown slot key is rejected', function () {
    actingAsAdmin('catalog-manager');

    $this->putJson('/api/v1/admin/site-media/not-a-real-slot', [])->assertNotFound();
});
