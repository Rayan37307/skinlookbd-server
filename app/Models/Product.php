<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'brand_id', 'name', 'slug', 'sku', 'description', 'short_description', 'ingredients',
    'additional_information', 'base_price', 'sale_price', 'cost_price', 'status', 'track_inventory',
    'stock_quantity', 'free_shipping', 'meta_title', 'meta_description', 'focus_keyword', 'canonical_url',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_price' => 'integer',
            'cost_price' => 'integer',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'integer',
            'free_shipping' => 'boolean',
            'additional_information' => 'array',
        ];
    }

    /**
     * A product can belong to any number of categories, spanning different top-level trees
     * (e.g. both "Skincare > Cleansers" and "Haircare > Shampoo"), with none of them treated
     * as primary. Callers that need a single representative category (a decorative flourish,
     * a "related products" heuristic) should just take the first one.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return BelongsToMany<SkinType, $this>
     */
    public function skinTypes(): BelongsToMany
    {
        return $this->belongsToMany(SkinType::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return BelongsToMany<Label, $this>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'product_label');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'related_products', 'product_id', 'related_product_id');
    }

    /**
     * The effective selling price: the active sale price if set, otherwise the regular price.
     */
    public function effectivePrice(): int
    {
        return $this->sale_price ?? $this->base_price;
    }

    public function isOnSale(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->base_price;
    }

    public function hasVariants(): bool
    {
        return $this->variants->isNotEmpty();
    }

    /**
     * Variant stock takes priority when the product has variants; otherwise falls back to the
     * product's own stock_quantity/track_inventory.
     */
    public function isInStock(): bool
    {
        if ($this->hasVariants()) {
            return $this->variants->contains(fn (ProductVariant $variant) => $variant->inStock());
        }

        return ! $this->track_inventory || $this->stock_quantity > 0;
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereHas('variants', fn (Builder $variants) => $variants->where('stock_quantity', '>', 0))
                ->orWhere(function (Builder $q2) {
                    $q2->doesntHave('variants')
                        ->where(function (Builder $q3) {
                            $q3->where('track_inventory', false)
                                ->orWhere('stock_quantity', '>', 0);
                        });
                });
        });
    }
}
