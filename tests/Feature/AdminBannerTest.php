<?php

use App\Models\Banner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a customer cannot manage banners', function () {
    actingAsUser();

    $this->postJson('/api/v1/admin/banners', [])->assertForbidden();
});

test('a catalog manager can create a banner with an image', function () {
    Storage::fake('public');
    actingAsAdmin('catalog-manager');

    $response = $this->postJson('/api/v1/admin/banners', [
        'title' => 'New Year Sale',
        'image' => UploadedFile::fake()->image('banner.jpg'),
    ]);

    $response->assertCreated()->assertJsonPath('banner.title', 'New Year Sale');
    Storage::disk('public')->assertExists($response->json('banner.image'));
});

test('a catalog manager can delete a banner', function () {
    Storage::fake('public');
    actingAsAdmin('catalog-manager');
    $banner = Banner::factory()->create(['image' => 'banners/existing.jpg']);
    Storage::disk('public')->put('banners/existing.jpg', 'fake-content');

    $this->deleteJson("/api/v1/admin/banners/{$banner->id}")->assertOk();

    expect(Banner::find($banner->id))->toBeNull();
    Storage::disk('public')->assertMissing('banners/existing.jpg');
});
