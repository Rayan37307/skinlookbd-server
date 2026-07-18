<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    /**
     * Real Dhaka neighbourhoods paired with their actual postal codes — used instead of
     * Faker's default US-style citySuffix()/postcode(), which don't fit a Bangladesh store.
     *
     * @var array<int, array{area: string, postal_code: string}>
     */
    private const DHAKA_AREAS = [
        ['area' => 'Gulshan', 'postal_code' => '1212'],
        ['area' => 'Banani', 'postal_code' => '1213'],
        ['area' => 'Dhanmondi', 'postal_code' => '1205'],
        ['area' => 'Mirpur', 'postal_code' => '1216'],
        ['area' => 'Uttara', 'postal_code' => '1230'],
        ['area' => 'Mohammadpur', 'postal_code' => '1207'],
        ['area' => 'Bashundhara R/A', 'postal_code' => '1229'],
        ['area' => 'Motijheel', 'postal_code' => '1000'],
        ['area' => 'Badda', 'postal_code' => '1212'],
        ['area' => 'Rampura', 'postal_code' => '1219'],
        ['area' => 'Khilgaon', 'postal_code' => '1219'],
        ['area' => 'Shyamoli', 'postal_code' => '1207'],
        ['area' => 'Lalmatia', 'postal_code' => '1207'],
        ['area' => 'Farmgate', 'postal_code' => '1215'],
        ['area' => 'Malibagh', 'postal_code' => '1217'],
    ];

    /**
     * Common Bangladeshi given + family names (English transliteration), used instead of
     * Faker's default en_US name() output which reads as Western names on a BD storefront.
     *
     * @var array<int, string>
     */
    private const CUSTOMER_NAMES = [
        'Rafiul Islam', 'Nusrat Jahan', 'Tanvir Ahmed', 'Farzana Akter', 'Shafiqul Islam',
        'Nadia Rahman', 'Imran Hossain', 'Sabrina Chowdhury', 'Mehedi Hasan', 'Tasnia Ferdous',
        'Arif Hossain', 'Sharmin Akter', 'Kamrul Hasan', 'Fahmida Yasmin', 'Shakil Ahmed',
        'Rumana Islam', 'Zubair Rahman', 'Nasrin Sultana', 'Habibur Rahman', 'Tania Zaman',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect(self::CUSTOMER_NAMES)
            ->take(15)
            ->each(function (string $name) {
                $customer = User::factory()->create([
                    'name' => $name,
                    'email' => Str::slug($name, '.').'@example.com',
                ]);
                $customer->assignRole('customer');

                $home = fake()->randomElement(self::DHAKA_AREAS);

                Address::factory()->for($customer)->create([
                    'recipient_name' => $name,
                    'address_line1' => 'House '.fake()->numberBetween(1, 40).', Road '.fake()->numberBetween(1, 27),
                    'city' => 'Dhaka',
                    'area' => $home['area'],
                    'postal_code' => $home['postal_code'],
                    'is_default' => true,
                ]);

                if (fake()->boolean(40)) {
                    $office = fake()->randomElement(self::DHAKA_AREAS);

                    Address::factory()->for($customer)->create([
                        'label' => 'Office',
                        'recipient_name' => $name,
                        'address_line1' => 'House '.fake()->numberBetween(1, 40).', Road '.fake()->numberBetween(1, 27),
                        'city' => 'Dhaka',
                        'area' => $office['area'],
                        'postal_code' => $office['postal_code'],
                    ]);
                }
            });
    }
}
