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
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'logo' => $this->brand->logo,
            ] : null),
            'short_description' => $this->short_description,
            'description' => $this->description,
            'ingredients' => $this->ingredients,
            'additional_information' => $this->additional_information,
            'regular_price' => $this->base_price,
            'sale_price' => $this->sale_price,
            'is_on_sale' => $this->isOnSale(),
            'free_shipping' => $this->free_shipping,
            'in_stock' => $this->whenLoaded('variants', fn () => $this->isInStock()),
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
                'url' => $image->url(),
                'alt' => $image->alt,
            ])),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'related_products' => ProductResource::collection($this->whenLoaded('relatedProducts')),
        ];
    }
}
