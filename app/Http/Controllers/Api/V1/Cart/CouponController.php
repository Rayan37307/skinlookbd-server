<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\ValidateCouponRequest;
use App\Models\Coupon;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

/**
 * @group Cart
 *
 * @authenticated
 */
class CouponController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    /**
     * Validate a coupon
     *
     * Checks a coupon code against the current cart and returns the discount preview
     * without redeeming it. Redemption happens at checkout.
     */
    public function validateCoupon(ValidateCouponRequest $request): JsonResponse
    {
        $coupon = Coupon::where('code', $request->string('code')->upper()->value())->first();

        abort_if(! $coupon, 422, 'This coupon code is invalid.');

        $cart = $this->carts->resolve($request)->load('items.productVariant');
        $subtotal = $cart->items->sum(fn ($item) => $item->productVariant->price * $item->quantity);

        abort_unless($coupon->isRedeemableBy($request->user(), $subtotal), 422, 'This coupon cannot be applied to your order.');

        $discount = $coupon->calculateDiscount($subtotal);

        return response()->json([
            'coupon' => [
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
            ],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
        ]);
    }
}
