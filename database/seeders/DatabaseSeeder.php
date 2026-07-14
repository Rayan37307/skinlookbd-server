<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            SkinTypeSeeder::class,
            ProductSeeder::class,
            ShippingZoneSeeder::class,
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '01700000000',
        ])->assignRole('customer');

        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'phone' => '01700000001',
        ])->assignRole('super-admin');
    }
}
