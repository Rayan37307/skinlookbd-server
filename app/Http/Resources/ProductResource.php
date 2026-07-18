<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'brand' => $this->brand,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->topCategory()->id,
                'name' => $this->topCategory()->name,
                'slug' => $this->topCategory()->slug,
            ]),
            'subcategory' => $this->whenLoaded('category', fn () => $this->subcategoryOrNull() ? [
                'id' => $this->subcategoryOrNull()->id,
                'name' => $this->subcategoryOrNull()->name,
                'slug' => $this->subcategoryOrNull()->slug,
            ] : null),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
            'labels' => $this->whenLoaded('labels', fn () => $this->labels->map(fn ($label) => [
                'name' => $label->name,
                'color' => $label->color,
                'icon' => $label->icon,
            ])),
            'regular_price' => $this->base_price,
            'sale_price' => $this->sale_price,
            'is_on_sale' => $this->isOnSale(),
            'price_from' => $this->whenLoaded('variants', fn () => $this->variants->min('price') ?? $this->effectivePrice()),
            'primary_image' => $this->whenLoaded('images', fn () => $this->images->first()?->path),
            'free_shipping' => $this->free_shipping,
            'in_stock' => $this->whenLoaded('variants', fn () => $this->isInStock()),
        ];
    }
}
