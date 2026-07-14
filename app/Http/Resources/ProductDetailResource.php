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
            'brand' => $this->brand,
            'description' => $this->description,
            'ingredients' => $this->ingredients,
            'base_price' => $this->base_price,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'skin_types' => $this->whenLoaded('skinTypes', fn () => $this->skinTypes->pluck('name')),
            'images' => $this->whenLoaded('images', fn () => $this->images->pluck('path')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}
