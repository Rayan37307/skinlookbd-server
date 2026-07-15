<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()
            ->count(15)
            ->create()
            ->each(function (User $customer) {
                $customer->assignRole('customer');

                Address::factory()->for($customer)->create(['is_default' => true]);

                if (fake()->boolean(40)) {
                    Address::factory()->for($customer)->create(['label' => 'Office']);
                }
            });
    }
}
