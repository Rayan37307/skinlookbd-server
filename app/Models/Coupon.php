<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'code', 'method', 'discount_type', 'discount_value', 'max_discount',
    'min_order_value', 'first_order_only', 'max_uses', 'max_uses_per_user',
    'allow_combination', 'starts_at', 'expires_at', 'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_discount' => 'integer',
            'first_order_only' => 'boolean',
            'allow_combination' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Automatic promotions apply without a code; code-based coupons must be entered by the
     * customer at checkout.
     */
    public function isAutomatic(): bool
    {
        return $this->method === 'automatic';
    }

    public function grantsFreeDelivery(): bool
    {
        return $this->discount_type === 'free_delivery';
    }

    /**
     * @return HasMany<CouponUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'coupon_category');
    }

    /**
     * @return BelongsToMany<Brand, $this>
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'coupon_brand');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'coupon_tag');
    }

    /**
     * A coupon with any product/category/brand/tag selection only discounts matching line
     * items; without one, it applies to the whole order.
     */
    public function hasRestrictions(): bool
    {
        return $this->products->isNotEmpty()
            || $this->categories->isNotEmpty()
            || $this->brands->isNotEmpty()
            || $this->tags->isNotEmpty();
    }

    /**
     * Selections are OR-ed together: a product matches if it is selected directly, sits in a
     * selected category, belongs to a selected brand, or carries a selected tag.
     *
     * Load `products`, `categories`, `brands` and `tags` on the coupon (and `categories`/`tags`
     * on the product) to avoid per-item queries — CouponService does this automatically.
     */
    public function matchesProduct(Product $product): bool
    {
        if ($this->products->contains('id', $product->id)) {
            return true;
        }

        if ($this->categories->isNotEmpty()
            && $product->categories->pluck('id')->intersect($this->categories->pluck('id'))->isNotEmpty()) {
            return true;
        }

        if ($this->brands->isNotEmpty()
            && $product->brand_id !== null
            && $this->brands->contains('id', $product->brand_id)) {
            return true;
        }

        return $this->tags->isNotEmpty()
            && $product->tags->pluck('id')->intersect($this->tags->pluck('id'))->isNotEmpty();
    }

    /**
     * `$user` is null for guest checkout — the per-user usage cap only applies to
     * identified accounts; guests are still subject to the global `max_uses` cap.
     *
     * `$subtotal` should be the subtotal of the items this coupon applies to (the eligible
     * subtotal for a restricted coupon), so `min_order_value` measures what the customer can
     * actually discount.
     */
    public function isRedeemableBy(?User $user, int $subtotal): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($subtotal < $this->min_order_value) {
            return false;
        }

        if ($this->first_order_only) {
            // Guests have no order history to check, so they can never redeem these.
            if (! $user || Order::where('user_id', $user->id)->exists()) {
                return false;
            }
        }

        if ($this->max_uses !== null && $this->usages()->count() >= $this->max_uses) {
            return false;
        }

        if ($user && $this->max_uses_per_user !== null
            && $this->usages()->where('user_id', $user->id)->count() >= $this->max_uses_per_user) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(int $subtotal): int
    {
        if ($this->grantsFreeDelivery()) {
            return 0;
        }

        $discount = match ($this->discount_type) {
            'percent' => (int) round($subtotal * $this->discount_value / 100),
            'flat' => $this->discount_value,
            default => 0,
        };

        if ($this->max_discount !== null) {
            $discount = min($discount, $this->max_discount);
        }

        return max(0, min($discount, $subtotal));
    }
}
