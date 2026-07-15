<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Order Manager',
            'email' => 'order-manager@example.com',
            'phone' => '01700000002',
        ])->assignRole('order-manager');

        User::factory()->create([
            'name' => 'Catalog Manager',
            'email' => 'catalog-manager@example.com',
            'phone' => '01700000003',
        ])->assignRole('catalog-manager');
    }
}
