<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
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
            'sku' => $this->sku,
            'brand' => $this->brand,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'ingredients' => $this->ingredients,
            'additional_information' => $this->additional_information,
            'regular_price' => $this->base_price,
            'sale_price' => $this->sale_price,
            'is_on_sale' => $this->isOnSale(),
            'free_shipping' => $this->free_shipping,
            'in_stock' => $this->whenLoaded('variants', fn () => $this->isInStock()),
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
            'skin_types' => $this->whenLoaded('skinTypes', fn () => $this->skinTypes->pluck('name')),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
            'labels' => $this->whenLoaded('labels', fn () => $this->labels->map(fn ($label) => [
                'name' => $label->name,
                'color' => $label->color,
                'icon' => $label->icon,
            ])),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'type' => $image->type,
                'path' => $image->path,
                'alt' => $image->alt,
            ])),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'related_products' => ProductResource::collection($this->whenLoaded('relatedProducts')),
        ];
    }
}
