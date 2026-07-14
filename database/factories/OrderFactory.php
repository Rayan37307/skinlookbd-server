<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(500, 5000);
        $shippingCharge = 80;

        return [
            'order_number' => Order::generateOrderNumber(),
            'user_id' => User::factory(),
            'address_id' => Address::factory(),
            'coupon_id' => null,
            'status' => 'pending',
            'payment_method' => 'cod',
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'shipping_charge' => $shippingCharge,
            'total' => $subtotal + $shippingCharge,
            'recipient_name' => fake()->name(),
            'recipient_phone' => '01'.fake()->numerify('#########'),
            'shipping_address_line1' => fake()->streetAddress(),
            'shipping_address_line2' => null,
            'shipping_city' => 'Dhaka',
            'shipping_area' => null,
            'shipping_postal_code' => fake()->postcode(),
            'notes' => null,
        ];
    }
}
