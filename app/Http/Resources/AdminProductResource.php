<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
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
            'base_price' => $this->base_price,
            'sale_price' => $this->sale_price,
            'cost_price' => $this->cost_price,
            'is_on_sale' => $this->isOnSale(),
            'status' => $this->status,
            'track_inventory' => $this->track_inventory,
            'stock_quantity' => $this->stock_quantity,
            'in_stock' => $this->whenLoaded('variants', fn () => $this->isInStock()),
            'free_shipping' => $this->free_shipping,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'focus_keyword' => $this->focus_keyword,
            'canonical_url' => $this->canonical_url,
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
                'id' => $label->id,
                'name' => $label->name,
                'color' => $label->color,
                'icon' => $label->icon,
            ])),
            'related_products' => $this->whenLoaded('relatedProducts', fn () => $this->relatedProducts->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
            ])),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'type' => $image->type,
                'path' => $image->path,
                'url' => $image->url(),
                'alt' => $image->alt,
                'sort_order' => $image->sort_order,
            ])),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
