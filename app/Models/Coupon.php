<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'discount_type', 'discount_value', 'min_order_value', 'max_uses', 'max_uses_per_user', 'starts_at', 'expires_at', 'is_active'])]
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
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CouponUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * `$user` is null for guest checkout — the per-user usage cap only applies to
     * identified accounts; guests are still subject to the global `max_uses` cap.
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
        return match ($this->discount_type) {
            'percent' => (int) round($subtotal * $this->discount_value / 100),
            'flat' => min($this->discount_value, $subtotal),
        };
    }
}
