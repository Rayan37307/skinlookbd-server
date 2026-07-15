<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Reviews require an order_item_id as proof of purchase, so only delivered orders
     * are eligible — matching the real review-eligibility rule enforced at the API layer.
     */
    public function run(): void
    {
        $deliveredOrders = Order::where('status', 'delivered')->with('items')->get();

        foreach ($deliveredOrders as $order) {
            foreach ($order->items as $item) {
                if (! fake()->boolean(70)) {
                    continue;
                }

                $review = new Review([
                    'user_id' => $order->user_id,
                    'order_item_id' => $item->id,
                    'rating' => fake()->numberBetween(3, 5),
                    'title' => fake()->sentence(4),
                    'body' => fake()->paragraph(),
                    'status' => fake()->boolean(80) ? 'approved' : 'pending',
                ]);
                $review->product_id = $item->product_id;
                $review->save();
            }
        }
    }
}
