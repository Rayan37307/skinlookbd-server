<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * A real top-nav structure matching the actual category tree (see CategorySeeder),
     * plus a highlighted "Sale" button and a "New Arrivals" link — so the storefront nav
     * has something real to render out of the box.
     */
    public function run(): void
    {
        $sortOrder = 0;

        $tree = [
            'Skincare' => ['Cleansers', 'Toners', 'Serums', 'Moisturizers'],
            'Sun Care' => ['Sunscreens', 'After Sun'],
            'Hair Care' => ['Shampoos', 'Conditioners', 'Hair Oils'],
        ];

        foreach ($tree as $topName => $subNames) {
            $topCategory = Category::where('slug', Str::slug($topName))->whereNull('parent_id')->first();

            if (! $topCategory) {
                continue;
            }

            $topMenu = Menu::create([
                'label' => $topName,
                'type' => 'category',
                'target' => $topCategory->slug,
                'sort_order' => $sortOrder++,
                'is_active' => true,
            ]);

            $childSort = 0;

            foreach ($subNames as $subName) {
                $subCategory = Category::where('slug', Str::slug($subName))
                    ->where('parent_id', $topCategory->id)
                    ->first();

                if (! $subCategory) {
                    continue;
                }

                Menu::create([
                    'parent_id' => $topMenu->id,
                    'label' => $subName,
                    'type' => 'category',
                    'target' => $subCategory->slug,
                    'sort_order' => $childSort++,
                    'is_active' => true,
                ]);
            }
        }

        Menu::create([
            'label' => 'New Arrivals',
            'type' => 'custom_url',
            'target' => '/products?tag=new-arrival',
            'sort_order' => $sortOrder++,
            'is_active' => true,
        ]);

        Menu::create([
            'label' => 'Sale',
            'type' => 'button',
            'target' => '/products?label=sale',
            'style' => 'highlight',
            'sort_order' => $sortOrder++,
            'is_active' => true,
        ]);
    }
}
