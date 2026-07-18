<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect(['Bestseller', 'New Arrival', 'Vegan', 'Cruelty-Free', 'Fragrance-Free', 'Organic', 'Sale'])
            ->each(fn (string $name) => Tag::create([
                'name' => $name,
                'slug' => Str::slug($name),
            ]));
    }
}
