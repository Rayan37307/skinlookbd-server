<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Coupon::create([
            'code' => 'WELCOME10',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order_value' => 500,
            'max_uses' => null,
            'max_uses_per_user' => 1,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'FLAT100',
            'discount_type' => 'flat',
            'discount_value' => 100,
            'min_order_value' => 1000,
            'max_uses' => 200,
            'max_uses_per_user' => null,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'EIDSALE',
            'discount_type' => 'percent',
            'discount_value' => 20,
            'min_order_value' => 0,
            'max_uses' => null,
            'max_uses_per_user' => null,
            'starts_at' => now()->addDays(14),
            'expires_at' => now()->addDays(21),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'SUMMER25',
            'discount_type' => 'percent',
            'discount_value' => 25,
            'min_order_value' => 0,
            'max_uses' => null,
            'max_uses_per_user' => null,
            'starts_at' => now()->subDays(60),
            'expires_at' => now()->subDays(30),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'RETIRED5',
            'discount_type' => 'flat',
            'discount_value' => 50,
            'min_order_value' => 0,
            'max_uses' => null,
            'max_uses_per_user' => null,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => false,
        ]);
    }
}
