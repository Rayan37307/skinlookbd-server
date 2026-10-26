<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sku', 'size_label', 'price_override', 'stock_quantity'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * True while any order referencing this variant can still restock it.
     * Settled orders (cancelled/returned) never touch stock again, so they do
     * not block deletion.
     */
    public function hasActiveOrders(): bool
    {
        return $this->orderItems()
            ->whereHas('order', fn ($query) => $query->whereNotIn('status', Order::SETTLED_STATUSES))
            ->exists();
    }

    /**
     * @return Attribute<int, never>
     */
    protected function price(): Attribute
    {
        return Attribute::get(fn () => $this->price_override ?? $this->product->effectivePrice());
    }

    public function inStock(): bool
    {
        return $this->stock_quantity > 0;
    }
}
