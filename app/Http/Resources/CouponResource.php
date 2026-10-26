<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
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
            'code' => $this->code,
            'method' => $this->method,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'max_discount' => $this->max_discount,
            'min_order_value' => $this->min_order_value,
            'first_order_only' => $this->first_order_only,
            'max_uses' => $this->max_uses,
            'max_uses_per_user' => $this->max_uses_per_user,
            'allow_combination' => $this->allow_combination,
            'starts_at' => $this->starts_at,
            'expires_at' => $this->expires_at,
            'is_active' => $this->is_active,
            'applies_to' => [
                'products' => $this->products->map(fn ($product) => ['id' => $product->id, 'name' => $product->name])->values(),
                'categories' => $this->categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->values(),
                'brands' => $this->brands->map(fn ($brand) => ['id' => $brand->id, 'name' => $brand->name])->values(),
                'tags' => $this->tags->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name])->values(),
            ],
            'created_at' => $this->created_at,
        ];
    }
}
