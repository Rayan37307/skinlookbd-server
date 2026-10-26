<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CouponService
{
    /**
     * Evaluates a coupon against a cart.
     *
     * For a restricted coupon (one with at least one product/category/brand/tag selected),
     * only matching line items are eligible: the discount and `min_order_value` are both
     * measured against that eligible subtotal, and a cart with no matching items is rejected.
     * An unrestricted coupon is evaluated against the whole cart, as before.
     *
     * @param  Collection<int, CartItem>  $items
     * @return array{subtotal: int, eligible_subtotal: int, discount: int, free_delivery: bool}|null null when the coupon is not redeemable
     */
    public function evaluate(Coupon $coupon, Collection $items, ?User $user): ?array
    {
        $items->loadMissing([
            'productVariant.product.categories',
            'productVariant.product.brand',
            'productVariant.product.tags',
        ]);

        $coupon->loadMissing(['products', 'categories', 'brands', 'tags']);

        $subtotal = $this->subtotal($items);
        $restricted = $coupon->hasRestrictions();

        $eligibleItems = $restricted
            ? $items->filter(fn (CartItem $item): bool => $coupon->matchesProduct($item->productVariant->product))
            : $items;

        $eligibleSubtotal = $this->subtotal($eligibleItems);

        if ($restricted && $eligibleSubtotal === 0) {
            return null;
        }

        if (! $coupon->isRedeemableBy($user, $eligibleSubtotal)) {
            return null;
        }

        return [
            'subtotal' => $subtotal,
            'eligible_subtotal' => $eligibleSubtotal,
            'discount' => $coupon->calculateDiscount($eligibleSubtotal),
            'free_delivery' => $coupon->grantsFreeDelivery(),
        ];
    }

    /**
     * Finds the first active automatic promotion (lowest id, matching the order shown in the
     * admin panel) that the cart qualifies for.
     *
     * Automatic promotions are applied at checkout when the customer does not enter a code.
     * Only one automatic promotion is chosen: a second one is never stacked.
     *
     * @param  Collection<int, CartItem>  $items
     * @return array{coupon: Coupon, application: array{subtotal: int, eligible_subtotal: int, discount: int, free_delivery: bool}}|null
     */
    public function findAutomatic(Collection $items, ?User $user): ?array
    {
        $coupons = Coupon::query()
            ->where('method', 'automatic')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        foreach ($coupons as $coupon) {
            $application = $this->evaluate($coupon, $items, $user);

            if ($application !== null) {
                return ['coupon' => $coupon, 'application' => $application];
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, CartItem>  $items
     */
    private function subtotal(Collection $items): int
    {
        return $items->sum(fn (CartItem $item): int => $item->productVariant->price * $item->quantity);
    }
}
