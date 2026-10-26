<?php

namespace App\Services;

use App\Models\ShippingZone;
use InvalidArgumentException;

class ShippingService
{
    private const DHAKA_CITY = 'Dhaka';

    /**
     * Exact strings the frontend sends for its "Dhaka Suburb" tier — must stay byte-for-byte
     * in sync with bd-locations.ts on the frontend, including the "(Dhaka Suburb)" suffix.
     *
     * @var array<int, string>
     */
    private const SUBURB_CITIES = [
        'Ashulia (Dhaka Suburb)',
        'Dhamrai (Dhaka Suburb)',
        'Dohar (Dhaka Suburb)',
        'Hemayetpur (Dhaka Suburb)',
        'Keraniganj (Dhaka Suburb)',
        'Nawabganj (Dhaka Suburb)',
        'Savar (Dhaka Suburb)',
    ];

    /**
     * The remaining 63 Bangladesh districts (all 64 minus Dhaka itself). Must stay in sync
     * with bd-locations.ts on the frontend.
     *
     * @var array<int, string>
     */
    private const NORMAL_CITIES = [
        'Bagerhat', 'Bandarban', 'Barguna', 'Barishal', 'Bhola', 'Bogura', 'Brahmanbaria',
        'Chandpur', 'Chapainawabganj', 'Chattogram', 'Chuadanga', "Cox's Bazar", 'Cumilla',
        'Dinajpur', 'Faridpur', 'Feni', 'Gaibandha', 'Gazipur', 'Gopalganj', 'Habiganj',
        'Jamalpur', 'Jashore', 'Jhalokati', 'Jhenaidah', 'Joypurhat', 'Khagrachari', 'Khulna',
        'Kishoreganj', 'Kurigram', 'Kushtia', 'Lakshmipur', 'Lalmonirhat', 'Madaripur', 'Magura',
        'Manikganj', 'Meherpur', 'Moulvibazar', 'Munshiganj', 'Mymensingh', 'Naogaon', 'Narail',
        'Narayanganj', 'Narsingdi', 'Natore', 'Netrokona', 'Nilphamari', 'Noakhali', 'Pabna',
        'Panchagarh', 'Patuakhali', 'Pirojpur', 'Rajbari', 'Rajshahi', 'Rangamati', 'Rangpur',
        'Satkhira', 'Shariatpur', 'Sherpur', 'Sirajganj', 'Sunamganj', 'Sylhet', 'Tangail',
        'Thakurgaon',
    ];

    /**
     * The shipping table used when no zone has been configured in the admin panel yet (or when
     * a city isn't covered by any configured zone). The Delivery settings page starts from
     * exactly these three tiers, so a fresh install behaves the same as the old hardcoded
     * pricing until an admin edits it.
     *
     * @var array<int, array{name: string, areas: array<int, string>, charge: int, eta_days: int}>
     */
    public const DEFAULT_ZONES = [
        [
            'name' => 'Dhaka Metro',
            'areas' => [self::DHAKA_CITY],
            'charge' => 70,
            'eta_days' => 2,
        ],
        [
            'name' => 'Dhaka Suburb',
            'areas' => self::SUBURB_CITIES,
            'charge' => 100,
            'eta_days' => 3,
        ],
        [
            'name' => 'Rest of Bangladesh',
            'areas' => self::NORMAL_CITIES,
            'charge' => 130,
            'eta_days' => 5,
        ],
    ];

    /**
     * The full whitelist of city strings the checkout/address forms will accept — the single
     * source of truth `Rule::in(...)` validation (CheckoutRequest, Store/UpdateAddressRequest,
     * ShippingEstimateRequest) is built from, so an unrecognized city is rejected at the
     * validation layer rather than silently priced as "normal" tier.
     *
     * @return array<int, string>
     */
    public static function validCities(): array
    {
        return [self::DHAKA_CITY, ...self::SUBURB_CITIES, ...self::NORMAL_CITIES];
    }

    public function isValidCity(string $city): bool
    {
        return in_array($city, self::validCities(), true);
    }

    /**
     * Quote delivery for a city.
     *
     * Charges and ETAs come from the zones configured in the Delivery settings page. Cities
     * that no configured zone covers fall back to the built-in tiers in DEFAULT_ZONES.
     *
     * `$subtotal` is the cart subtotal *before* any coupon discount, so a coupon can never
     * accidentally take an order below (or push it over) a free-delivery threshold.
     *
     * @return array{zone: ?string, charge: int, base_charge: int, eta_days: int, is_free_delivery: bool, free_delivery_threshold: ?int}
     *
     * @throws InvalidArgumentException if $city isn't one of the whitelisted values and isn't
     *                                  covered by a configured zone. Callers should validate against validCities()
     *                                  first (via FormRequest) so this is only ever a defense-in-depth check.
     */
    public function calculate(string $city, ?int $subtotal = null): array
    {
        $zone = $this->matchingZone($city);
        $default = self::defaultTierFor($city);

        if (! $zone && ! $default) {
            throw new InvalidArgumentException("Unrecognized shipping city: {$city}");
        }

        $baseCharge = $zone?->rate?->charge ?? $default['charge'] ?? 0;
        $etaDays = $zone?->rate?->eta_days ?? $default['eta_days'] ?? 0;
        $threshold = $zone?->rate?->free_delivery_threshold;

        $isFreeDelivery = $threshold !== null && $subtotal !== null && $subtotal >= $threshold;

        return [
            'zone' => $zone?->name,
            'charge' => $isFreeDelivery ? 0 : $baseCharge,
            'base_charge' => $baseCharge,
            'eta_days' => $etaDays,
            'is_free_delivery' => $isFreeDelivery,
            'free_delivery_threshold' => $threshold,
        ];
    }

    /**
     * First configured zone (in id order, which is the order shown in the admin panel) whose
     * areas include the city.
     */
    private function matchingZone(string $city): ?ShippingZone
    {
        return ShippingZone::with('rate')
            ->orderBy('id')
            ->get()
            ->first(fn (ShippingZone $zone) => $zone->matches($city));
    }

    /**
     * @return array{charge: int, eta_days: int}|null
     */
    private static function defaultTierFor(string $city): ?array
    {
        foreach (self::DEFAULT_ZONES as $tier) {
            if (in_array($city, $tier['areas'], true)) {
                return ['charge' => $tier['charge'], 'eta_days' => $tier['eta_days']];
            }
        }

        return null;
    }
}
