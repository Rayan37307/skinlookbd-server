<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items', fn () => $this->items);

        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($items ?: []),
            'subtotal' => $items ? $items->sum(fn ($item) => $item->productVariant->price * $item->quantity) : 0,
        ];
    }
}
