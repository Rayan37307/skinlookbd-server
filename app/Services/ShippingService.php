<?php

namespace App\Services;

use App\Models\ShippingZone;

class ShippingService
{
    private const DEFAULT_CHARGE = 120;

    private const DEFAULT_ETA_DAYS = 5;

    /**
     * @return array{charge: int, eta_days: int}
     */
    public function calculate(string $city, ?string $area = null): array
    {
        $zone = ShippingZone::with('rate')->get()->first(fn (ShippingZone $zone) => $zone->matches($city, $area));

        if ($zone?->rate) {
            return [
                'charge' => $zone->rate->charge,
                'eta_days' => $zone->rate->eta_days ?? self::DEFAULT_ETA_DAYS,
            ];
        }

        return [
            'charge' => self::DEFAULT_CHARGE,
            'eta_days' => self::DEFAULT_ETA_DAYS,
        ];
    }
}
