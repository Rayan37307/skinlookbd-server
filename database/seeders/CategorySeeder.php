<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tree = [
            'Skincare' => ['Cleansers', 'Toners', 'Serums', 'Moisturizers'],
            'Sun Care' => ['Sunscreens', 'After Sun'],
            'Hair Care' => ['Shampoos', 'Conditioners', 'Hair Oils'],
        ];

        foreach ($tree as $parentName => $children) {
            $parent = Category::create([
                'name' => $parentName,
                'slug' => Str::slug($parentName),
                'is_active' => true,
            ]);

            foreach ($children as $childName) {
                Category::create([
                    'parent_id' => $parent->id,
                    'name' => $childName,
                    'slug' => Str::slug($childName),
                    'is_active' => true,
                ]);
            }
        }
    }
}
