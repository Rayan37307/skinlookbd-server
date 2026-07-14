<?php

namespace Database\Seeders;

use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            'Dhaka Metro' => ['areas' => ['Dhaka'], 'charge' => 60, 'eta_days' => 2],
            'Chittagong' => ['areas' => ['Chittagong', 'Chattogram'], 'charge' => 100, 'eta_days' => 4],
        ];

        foreach ($zones as $name => $config) {
            ShippingZone::create(['name' => $name, 'areas' => $config['areas']])
                ->rate()->create(['charge' => $config['charge'], 'eta_days' => $config['eta_days']]);
        }
    }
}
