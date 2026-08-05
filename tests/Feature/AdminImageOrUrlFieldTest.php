<?php

use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Support\ImageOrUrlField;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

function actingAsFilamentAdminForImages(string $role = 'super-admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('ImageOrUrlField::combine prefers the upload but falls back to the url', function () {
    expect(ImageOrUrlField::combine(['image' => 'categories/foo.jpg', 'image_url' => 'https://example.com/bar.jpg'], 'image'))
        ->toBe(['image' => 'categories/foo.jpg']);

    expect(ImageOrUrlField::combine(['image' => null, 'image_url' => 'https://example.com/bar.jpg'], 'image'))
        ->toBe(['image' => 'https://example.com/bar.jpg']);

    expect(ImageOrUrlField::combine(['image' => null, 'image_url' => null], 'image'))
        ->toBe(['image' => null]);
});

test('ImageOrUrlField::split routes an http(s) value to the url field and a disk path to the upload field', function () {
    expect(ImageOrUrlField::split(['image' => 'https://example.com/bar.jpg'], 'image'))
        ->toBe(['image' => null, 'image_url' => 'https://example.com/bar.jpg']);

    expect(ImageOrUrlField::split(['image' => 'categories/foo.jpg'], 'image'))
        ->toBe(['image' => 'categories/foo.jpg', 'image_url' => null]);

    expect(ImageOrUrlField::split(['image' => null], 'image'))
        ->toBe(['image' => null, 'image_url' => null]);
});

test('an admin can create a category using an image URL instead of uploading a file', function () {
    actingAsFilamentAdminForImages();

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'image_url' => 'https://example.com/category.jpg',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Category::where('slug', 'test-category')->first()->image)->toBe('https://example.com/category.jpg');
});

test('editing a category with a URL-based image pre-fills the url field, not the uploader', function () {
    actingAsFilamentAdminForImages();
    $category = Category::factory()->create(['image' => 'https://example.com/existing.jpg']);

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->assertFormSet([
            'image' => null,
            'image_url' => 'https://example.com/existing.jpg',
        ]);
});

test('an admin can create a brand using a logo URL instead of uploading a file', function () {
    actingAsFilamentAdminForImages();

    Livewire::test(CreateBrand::class)
        ->fillForm([
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'logo_url' => 'https://example.com/logo.png',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Brand::where('slug', 'test-brand')->first()->logo)->toBe('https://example.com/logo.png');
});

test('editing a brand with a URL-based logo pre-fills the url field, not the uploader', function () {
    actingAsFilamentAdminForImages();
    $brand = Brand::factory()->create(['logo' => 'https://example.com/existing-logo.png']);

    Livewire::test(EditBrand::class, ['record' => $brand->getRouteKey()])
        ->assertFormSet([
            'logo' => null,
            'logo_url' => 'https://example.com/existing-logo.png',
        ]);
});

test('an admin can create a banner using image URLs for both the desktop and mobile image', function () {
    actingAsFilamentAdminForImages();

    Livewire::test(CreateBanner::class)
        ->fillForm([
            'title' => 'Test Banner',
            'image_url' => 'https://example.com/desktop.jpg',
            'mobile_image_url' => 'https://example.com/mobile.jpg',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $banner = Banner::where('title', 'Test Banner')->first();
    expect($banner->image)->toBe('https://example.com/desktop.jpg');
    expect($banner->mobile_image)->toBe('https://example.com/mobile.jpg');
});

test('editing a banner with URL-based images pre-fills both url fields, not the uploaders', function () {
    actingAsFilamentAdminForImages();
    $banner = Banner::factory()->create([
        'image' => 'https://example.com/existing-desktop.jpg',
        'mobile_image' => 'https://example.com/existing-mobile.jpg',
    ]);

    Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
        ->assertFormSet([
            'image' => null,
            'image_url' => 'https://example.com/existing-desktop.jpg',
            'mobile_image' => null,
            'mobile_image_url' => 'https://example.com/existing-mobile.jpg',
        ]);
});

test('a product edit page with its images relation manager renders fine', function () {
    actingAsFilamentAdminForImages();
    $product = Product::factory()->create();
    $product->images()->create(['type' => 'image', 'path' => 'https://example.com/existing-product.jpg', 'sort_order' => 0]);

    test()->get("/admin/products/{$product->id}/edit")->assertOk();
});
