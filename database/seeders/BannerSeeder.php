<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banners = [
            ['title' => 'New Arrivals: Korean Skincare', 'sort_order' => 0, 'is_active' => true],
            ['title' => 'Sunscreen Season — Up to 20% Off', 'sort_order' => 1, 'is_active' => true],
            ['title' => 'Free Shipping in Dhaka Metro', 'sort_order' => 2, 'is_active' => true],
            ['title' => 'Eid Collection (Coming Soon)', 'sort_order' => 3, 'is_active' => false],
        ];

        foreach ($banners as $banner) {
            Banner::create([
                'title' => $banner['title'],
                'image' => 'banners/'.fake()->uuid().'.jpg',
                'link_url' => fake()->url(),
                'sort_order' => $banner['sort_order'],
                'starts_at' => null,
                'expires_at' => null,
                'is_active' => $banner['is_active'],
            ]);
        }
    }
}
