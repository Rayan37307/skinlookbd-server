<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variant = $this->productVariant;

        return [
            'id' => $this->id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'product_slug' => $variant->product->slug,
            'image' => $variant->product->images->first()?->url(),
            'sku' => $variant->sku,
            'size_label' => $variant->size_label,
            'unit_price' => $variant->price,
            'quantity' => $this->quantity,
            'line_total' => $variant->price * $this->quantity,
            'in_stock' => $variant->inStock(),
            'available_stock' => $variant->stock_quantity,
        ];
    }
}
