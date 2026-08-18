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
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'name' => $category->parent->name,
                    'slug' => $category->parent->slug,
                ] : null,
            ])),
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
            'primary_image' => $this->whenLoaded('images', fn () => $this->images->first()?->url()),
            'free_shipping' => $this->free_shipping,
            'in_stock' => $this->whenLoaded('variants', fn () => $this->isInStock()),
        ];
    }
}
