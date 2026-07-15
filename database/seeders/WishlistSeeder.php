<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class WishlistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = User::role('customer')->get();
        $products = Product::active()->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        $customers->each(function (User $customer) use ($products) {
            if (! fake()->boolean(60)) {
                return;
            }

            $products->random(min(random_int(1, 4), $products->count()))
                ->each(fn (Product $product) => $customer->wishlists()->firstOrCreate([
                    'product_id' => $product->id,
                ]));
        });
    }
}
