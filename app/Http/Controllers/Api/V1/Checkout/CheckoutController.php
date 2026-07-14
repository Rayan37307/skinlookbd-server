<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CartService;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @group Checkout
 *
 * @authenticated
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly ShippingService $shipping,
    ) {}

    /**
     * Place an order
     *
     * Validates stock, applies an optional coupon, computes the shipping charge for
     * the chosen address, and atomically creates the order, order items, and payment
     * record, decrementing variant stock and clearing the cart.
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user();
        $cart = $this->carts->resolve($request)->load('items.productVariant.product');

        abort_if($cart->items->isEmpty(), 422, 'Your cart is empty.');

        foreach ($cart->items as $item) {
            abort_if(
                $item->quantity > $item->productVariant->stock_quantity,
                422,
                "Not enough stock for {$item->productVariant->product->name} ({$item->productVariant->size_label})."
            );
        }

        $address = Address::where('user_id', $user->id)->findOrFail($request->integer('address_id'));

        $subtotal = $cart->items->sum(fn ($item) => $item->productVariant->price * $item->quantity);

        $coupon = null;
        $discountTotal = 0;

        if ($code = $request->string('coupon_code')->upper()->value()) {
            $coupon = Coupon::where('code', $code)->first();

            abort_if(! $coupon || ! $coupon->isRedeemableBy($user, $subtotal), 422, 'This coupon cannot be applied to your order.');

            $discountTotal = $coupon->calculateDiscount($subtotal);
        }

        $shipping = $this->shipping->calculate($address->city, $address->area);
        $shippingCharge = $shipping['charge'];

        $total = max(0, $subtotal - $discountTotal) + $shippingCharge;

        $order = DB::transaction(function () use ($request, $user, $cart, $address, $coupon, $subtotal, $discountTotal, $shippingCharge, $total) {
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'address_id' => $address->id,
                'coupon_id' => $coupon?->id,
                'status' => 'pending',
                'payment_method' => $request->string('payment_method')->value(),
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'shipping_charge' => $shippingCharge,
                'total' => $total,
                'recipient_name' => $address->recipient_name,
                'recipient_phone' => $address->phone,
                'shipping_address_line1' => $address->address_line1,
                'shipping_address_line2' => $address->address_line2,
                'shipping_city' => $address->city,
                'shipping_area' => $address->area,
                'shipping_postal_code' => $address->postal_code,
                'notes' => $request->string('notes')->value() ?: null,
            ]);

            foreach ($cart->items as $item) {
                $variant = $item->productVariant;

                $orderItem = $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name.' ('.$variant->size_label.')',
                    'sku' => $variant->sku,
                    'unit_price' => $variant->price,
                    'quantity' => $item->quantity,
                    'line_total' => $variant->price * $item->quantity,
                ]);

                $variant->decrement('stock_quantity', $item->quantity);

                $log = new InventoryLog([
                    'change_quantity' => -$item->quantity,
                    'reason' => 'sale',
                    'created_by' => $user->id,
                ]);
                $log->productVariant()->associate($variant);
                $log->reference()->associate($orderItem);
                $log->save();
            }

            if ($coupon) {
                $coupon->usages()->create(['user_id' => $user->id, 'order_id' => $order->id]);
            }

            Payment::create([
                'order_id' => $order->id,
                'method' => $order->payment_method,
                'status' => 'pending',
                'amount' => $order->total,
            ]);

            $cart->items()->delete();

            return $order;
        });

        return response()->json([
            'order' => new OrderResource($order->load('items')),
        ], 201);
    }
}
