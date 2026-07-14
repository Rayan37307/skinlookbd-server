<?php

namespace Database\Seeders;

use App\Models\SkinType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SkinTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect(['Oily', 'Dry', 'Combination', 'Sensitive', 'Normal', 'Acne-Prone', 'Mature'])
            ->each(fn (string $name) => SkinType::create([
                'name' => $name,
                'slug' => Str::slug($name),
            ]));
    }
}
