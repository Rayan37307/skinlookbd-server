<?php

namespace App\Services;

use InvalidArgumentException;

class ShippingService
{
    private const DHAKA_CITY = 'Dhaka';

    private const DHAKA_CHARGE = 70;

    private const DHAKA_ETA_DAYS = 2;

    private const SUBURB_CHARGE = 100;

    private const SUBURB_ETA_DAYS = 3;

    private const NORMAL_CHARGE = 130;

    private const NORMAL_ETA_DAYS = 5;

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
     * @return array{charge: int, eta_days: int}
     *
     * @throws InvalidArgumentException if $city isn't one of the whitelisted values. Callers
     *         should validate against validCities() first (via FormRequest) so this is only
     *         ever a defense-in-depth check, not the primary source of the user-facing error.
     */
    public function calculate(string $city): array
    {
        if ($city === self::DHAKA_CITY) {
            return ['charge' => self::DHAKA_CHARGE, 'eta_days' => self::DHAKA_ETA_DAYS];
        }

        if (in_array($city, self::SUBURB_CITIES, true)) {
            return ['charge' => self::SUBURB_CHARGE, 'eta_days' => self::SUBURB_ETA_DAYS];
        }

        if (in_array($city, self::NORMAL_CITIES, true)) {
            return ['charge' => self::NORMAL_CHARGE, 'eta_days' => self::NORMAL_ETA_DAYS];
        }

        throw new InvalidArgumentException("Unrecognized shipping city: {$city}");
    }
}
