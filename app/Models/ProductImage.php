<?php

namespace App\Models;

use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['type', 'path', 'alt', 'sort_order'])]
class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (ProductImage $image) {
            if ($image->type === 'image' && ! $image->isExternal()) {
                Storage::disk('public')->delete($image->path);
            }
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isExternal(): bool
    {
        return Str::startsWith($this->path, ['http://', 'https://']);
    }

    /**
     * A displayable URL for this image, whether `path` is a local storage-relative
     * path (admin uploads) or a full external URL (e.g. imported from a CSV catalog).
     */
    public function url(): string
    {
        return $this->isExternal() ? $this->path : Storage::disk('public')->url($this->path);
    }
}
