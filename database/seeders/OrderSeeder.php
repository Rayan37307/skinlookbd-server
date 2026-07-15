<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class OrderSeeder extends Seeder
{
    private const SHIPPING_CHARGE = 60;

    /**
     * Run the database seeds.
     *
     * Places each order the same way checkout does (decrementing stock, logging the
     * sale, creating a pending payment), then drives it to its final status through
     * OrderService so status-dependent side effects (restock on cancel/return, payment
     * marked paid on COD delivery) match what actually happens in production.
     */
    public function run(): void
    {
        $customers = User::role('customer')->get();
        $variants = ProductVariant::with('product')->get();
        $staff = User::role('super-admin')->first();

        if ($customers->isEmpty() || $variants->isEmpty() || ! $staff) {
            return;
        }

        $usableCoupons = Coupon::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->get();

        $orderService = app(OrderService::class);

        // [finalStatus, paymentMethod, daysAgo] — spread over the last month so the
        // dashboard's revenue widgets and 30-day chart have something to show.
        $plan = [
            ['pending', 'cod', 0],
            ['pending', 'cod', 0],
            ['confirmed', 'cod', 1],
            ['processing', 'cod', 2],
            ['shipped', 'cod', 4],
            ['shipped', 'bkash', 5],
            ['delivered', 'cod', 7],
            ['delivered', 'cod', 10],
            ['delivered', 'bkash', 14],
            ['delivered', 'cod', 18],
            ['delivered', 'cod', 22],
            ['delivered', 'cod', 27],
            ['cancelled', 'cod', 3],
            ['returned', 'cod', 12],
        ];

        foreach ($plan as [$finalStatus, $method, $daysAgo]) {
            $customer = $customers->random();
            $address = $customer->addresses()->first() ?? Address::factory()->for($customer)->create();

            $lines = $variants->shuffle()
                ->filter(fn (ProductVariant $variant) => $variant->stock_quantity > 0)
                ->take(random_int(1, 3))
                ->map(function (ProductVariant $variant) {
                    $quantity = min(2, $variant->stock_quantity);

                    return [
                        'variant' => $variant,
                        'quantity' => $quantity,
                        'unit_price' => $variant->price,
                        'line_total' => $variant->price * $quantity,
                    ];
                });

            if ($lines->isEmpty()) {
                continue;
            }

            $subtotal = $lines->sum('line_total');

            $coupon = $usableCoupons->isNotEmpty() && fake()->boolean(30)
                ? $usableCoupons->random()
                : null;
            $discountTotal = $coupon && $coupon->isRedeemableBy($customer, $subtotal)
                ? $coupon->calculateDiscount($subtotal)
                : 0;
            $coupon = $discountTotal > 0 ? $coupon : null;

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $customer->id,
                'address_id' => $address->id,
                'coupon_id' => $coupon?->id,
                'status' => 'pending',
                'payment_method' => $method,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'shipping_charge' => self::SHIPPING_CHARGE,
                'total' => $subtotal - $discountTotal + self::SHIPPING_CHARGE,
                'recipient_name' => $address->recipient_name,
                'recipient_phone' => $address->phone,
                'shipping_address_line1' => $address->address_line1,
                'shipping_address_line2' => $address->address_line2,
                'shipping_city' => $address->city,
                'shipping_area' => $address->area,
                'shipping_postal_code' => $address->postal_code,
                'notes' => null,
            ]);

            $this->createItems($order, $lines, $staff);

            if ($coupon) {
                $coupon->usages()->create(['user_id' => $customer->id, 'order_id' => $order->id]);
            }

            Payment::create([
                'order_id' => $order->id,
                'method' => $method,
                'status' => 'pending',
                'amount' => $order->total,
            ]);

            match ($finalStatus) {
                'pending' => null,
                'confirmed', 'processing', 'shipped' => $order->update(['status' => $finalStatus]),
                'delivered' => $orderService->markDelivered($order),
                'cancelled' => $orderService->cancel($order, $staff),
                'returned' => $orderService->refund($order, $staff, 'Customer requested a refund.'),
            };

            $order->forceFill([
                'created_at' => now()->subDays($daysAgo)->setTime(random_int(9, 21), random_int(0, 59)),
            ])->save();
        }

        // Guarantee the admin dashboard's low-stock widget has something to show,
        // rather than relying on the sales above happening to draw a variant down.
        $variants->random(min(3, $variants->count()))
            ->each(fn (ProductVariant $variant) => $variant->update(['stock_quantity' => random_int(0, 4)]));
    }

    /**
     * @param  Collection<int, array{variant: ProductVariant, quantity: int, unit_price: int, line_total: int}>  $lines
     */
    private function createItems(Order $order, Collection $lines, User $staff): void
    {
        foreach ($lines as $line) {
            /** @var ProductVariant $variant */
            $variant = $line['variant'];

            $orderItem = $order->items()->create([
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name.' ('.$variant->size_label.')',
                'sku' => $variant->sku,
                'unit_price' => $line['unit_price'],
                'quantity' => $line['quantity'],
                'line_total' => $line['line_total'],
            ]);

            $variant->decrement('stock_quantity', $line['quantity']);

            $log = new InventoryLog([
                'change_quantity' => -$line['quantity'],
                'reason' => 'sale',
                'created_by' => $staff->id,
            ]);
            $log->productVariant()->associate($variant);
            $log->reference()->associate($orderItem);
            $log->save();
        }
    }
}
