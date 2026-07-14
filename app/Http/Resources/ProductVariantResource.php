<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
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
            'sku' => $this->sku,
            'size_label' => $this->size_label,
            'price' => $this->price,
            'stock_quantity' => $this->stock_quantity,
            'in_stock' => $this->inStock(),
        ];
    }
}
