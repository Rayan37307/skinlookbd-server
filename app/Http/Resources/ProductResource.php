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
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'price_from' => $this->whenLoaded('variants', fn () => $this->variants->min('price') ?? $this->base_price),
            'primary_image' => $this->whenLoaded('images', fn () => $this->images->first()?->path),
            'in_stock' => $this->whenLoaded('variants', fn () => $this->variants->contains(fn ($variant) => $variant->inStock())),
        ];
    }
}
