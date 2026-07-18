<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Realistic review copy grouped by rating, so a 5-star review reads enthusiastic and a
     * 3-star one reads lukewarm, instead of unrelated randomly generated sentences. `{product}`
     * is replaced with the actual purchased product's name.
     *
     * @var array<int, array<int, array{title: string, body: string}>>
     */
    private const TEMPLATES = [
        5 => [
            ['title' => 'Absolutely love it!', 'body' => '{product} has become part of my daily routine — noticed a real difference within two weeks. Will definitely repurchase.'],
            ['title' => 'Holy grail product', 'body' => "I've tried a lot of things before and {product} is by far the best. Doesn't feel heavy at all and absorbs quickly."],
            ['title' => 'Exceeded my expectations', 'body' => 'Fast delivery from SkinLookBD and {product} works exactly as described. Highly recommend to anyone on the fence.'],
            ['title' => 'Worth every taka', 'body' => 'A bit pricier than local alternatives but the quality really shows. {product} made a noticeable difference for me.'],
            ['title' => 'Repeat purchase for sure', 'body' => 'This is my second bottle of {product} already. Consistent results every time and no irritation at all.'],
        ],
        4 => [
            ['title' => 'Really good, minor downside', 'body' => '{product} works well and I can see the difference, just wish the bottle was a bit bigger for the price.'],
            ['title' => 'Happy with this', 'body' => 'Does what it says on the label. Took about three weeks of using {product} to notice results but I\'m satisfied overall.'],
            ['title' => 'Good quality', 'body' => 'Packaging arrived safely and {product} feels genuine. Would buy again.'],
            ['title' => 'Solid choice', 'body' => 'Not a miracle product but {product} is a reliable addition to my routine. No complaints really.'],
            ['title' => 'Nice but a little pricey', 'body' => 'Quality of {product} is good, just wish it was priced a bit lower. Still recommend it though.'],
        ],
        3 => [
            ['title' => "It's okay", 'body' => '{product} does the basic job but I didn\'t notice anything special compared to what I used before.'],
            ['title' => 'Average experience', 'body' => 'Not bad, not amazing either. Might try something else next time instead of {product}.'],
            ['title' => 'Mixed feelings', 'body' => 'Texture of {product} is nice but I expected faster results given the price point.'],
            ['title' => 'Decent for the price', 'body' => '{product} gets the job done for everyday use, though I wouldn\'t call it a standout product.'],
            ['title' => 'Fine, nothing more', 'body' => 'Delivery was quick and packaging was fine, but {product} itself is just okay for me.'],
        ],
    ];

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

                $rating = fake()->numberBetween(3, 5);
                $template = fake()->randomElement(self::TEMPLATES[$rating]);
                $productName = $this->baseProductName($item);

                $review = new Review([
                    'user_id' => $order->user_id,
                    'order_item_id' => $item->id,
                    'rating' => $rating,
                    'title' => $template['title'],
                    'body' => str_replace('{product}', $productName, $template['body']),
                    'status' => fake()->boolean(80) ? 'approved' : 'pending',
                ]);
                $review->product_id = $item->product_id;
                $review->save();
            }
        }
    }

    /**
     * The product name without the "(size)" suffix OrderItem stores it with, so review
     * bodies read naturally ("CeraVe Foaming Facial Cleanser" rather than "... (236ml)").
     */
    private function baseProductName(OrderItem $item): string
    {
        return trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $item->product_name));
    }
}
