<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banners = [
            ['title' => 'New Arrivals: Korean Skincare', 'link_url' => '/products?tag=new-arrival', 'sort_order' => 0, 'is_active' => true],
            ['title' => 'Sunscreen Season — Up to 20% Off', 'link_url' => '/products?subcategory=sunscreens&label=sale', 'sort_order' => 1, 'is_active' => true],
            ['title' => 'Free Shipping in Dhaka Metro', 'link_url' => '/', 'sort_order' => 2, 'is_active' => true],
            ['title' => 'Eid Collection (Coming Soon)', 'link_url' => '/', 'sort_order' => 3, 'is_active' => false],
        ];

        foreach ($banners as $banner) {
            Banner::create([
                'title' => $banner['title'],
                'image' => 'banners/'.Str::slug($banner['title']).'.jpg',
                'link_url' => $banner['link_url'],
                'sort_order' => $banner['sort_order'],
                'starts_at' => null,
                'expires_at' => null,
                'is_active' => $banner['is_active'],
            ]);
        }
    }
}
