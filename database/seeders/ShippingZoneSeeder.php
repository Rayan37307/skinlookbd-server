<?php

namespace Database\Seeders;

use App\Models\ShippingZone;
use App\Services\ShippingService;
use Illuminate\Database\Seeder;

class ShippingZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ShippingService::DEFAULT_ZONES as $config) {
            $zone = ShippingZone::firstOrCreate(
                ['name' => $config['name']],
                ['areas' => $config['areas']],
            );

            $zone->rate()->firstOrCreate([], [
                'charge' => $config['charge'],
                'eta_days' => $config['eta_days'],
            ]);
        }
    }
}
