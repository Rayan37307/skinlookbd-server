<?php

namespace Database\Seeders;

use App\Models\Label;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LabelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $labels = [
            ['name' => 'Hot', 'color' => 'danger', 'icon' => '🔥'],
            ['name' => 'Sale', 'color' => 'warning', 'icon' => null],
            ['name' => 'New', 'color' => 'success', 'icon' => null],
            ['name' => 'Limited', 'color' => 'gray', 'icon' => null],
            ['name' => 'Exclusive', 'color' => 'primary', 'icon' => null],
        ];

        foreach ($labels as $label) {
            Label::create([
                'name' => $label['name'],
                'slug' => Str::slug($label['name']),
                'color' => $label['color'],
                'icon' => $label['icon'],
            ]);
        }
    }
}
