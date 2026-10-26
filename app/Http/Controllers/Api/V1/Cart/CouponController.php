<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\ValidateCouponRequest;
use App\Models\Coupon;
use App\Services\CartService;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Cart
 *
 * Works for both guests and authenticated customers, same as the cart endpoints.
 */
class CouponController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CouponService $coupons,
    ) {}

    /**
     * Validate a coupon
     *
     * Checks a coupon code against the current cart and returns the discount preview
     * without redeeming it. Redemption happens at checkout.
     *
     * For a coupon restricted to specific products/categories/brands/tags, only matching
     * line items are discounted: `eligible_subtotal` is the amount the discount was
     * calculated on, while `subtotal` is the full cart value. Free-delivery coupons report
     * `free_delivery: true` and a discount of zero.
     */
    public function validateCoupon(ValidateCouponRequest $request): JsonResponse
    {
        $coupon = Coupon::where('code', $request->string('code')->upper()->value())->first();

        abort_if(! $coupon, 422, 'This coupon code is invalid.');

        $cart = $this->carts->resolve($request)->load('items.productVariant.product');

        $application = $this->coupons->evaluate($coupon, $cart->items, $request->user());

        abort_if(! $application, 422, 'This coupon cannot be applied to your order.');

        return response()->json([
            'coupon' => [
                'name' => $coupon->name,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'max_discount' => $coupon->max_discount,
            ],
            'subtotal' => $application['subtotal'],
            'eligible_subtotal' => $application['eligible_subtotal'],
            'discount' => $application['discount'],
            'free_delivery' => $application['free_delivery'],
            'allow_combination' => $coupon->allow_combination,
            'total' => max(0, $application['subtotal'] - $application['discount']),
        ]);
    }

    /**
     * Preview the best active automatic promotion for the current cart.
     *
     * Returns the same discount preview shape as `validateCoupon` so the cart page can show the
     * customer what they will save before they reach checkout. The endpoint is read-only: it
     * does not redeem the coupon or mark it as used.
     */
    public function automaticCoupon(Request $request): JsonResponse
    {
        $cart = $this->carts->resolve($request)->load('items.productVariant.product');

        $result = $this->coupons->findAutomatic($cart->items, $request->user('sanctum'));

        if (! $result) {
            return response()->json(['coupon' => null]);
        }

        $coupon = $result['coupon'];
        $application = $result['application'];

        return response()->json([
            'coupon' => [
                'name' => $coupon->name,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'max_discount' => $coupon->max_discount,
                'allow_combination' => $coupon->allow_combination,
            ],
            'subtotal' => $application['subtotal'],
            'eligible_subtotal' => $application['eligible_subtotal'],
            'discount' => $application['discount'],
            'free_delivery' => $application['free_delivery'],
            'allow_combination' => $coupon->allow_combination,
            'total' => max(0, $application['subtotal'] - $application['discount']),
        ]);
    }
}
