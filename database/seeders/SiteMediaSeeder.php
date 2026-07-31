<?php

namespace Database\Seeders;

use App\Models\SiteMedia;
use Illuminate\Database\Seeder;

class SiteMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (array_keys(config('site_media.slots')) as $key) {
            SiteMedia::firstOrCreate(['key' => $key], [
                'image_path' => null,
                'link_url' => null,
                'is_active' => true,
            ]);
        }
    }
}
