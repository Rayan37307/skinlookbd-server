<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
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
            'label' => $this->label,
            'type' => $this->type,
            'target' => $this->target,
            'style' => $this->style,
            'highlight_color' => $this->highlight_color,
            'children' => MenuResource::collection($this->whenLoaded('children')),
        ];
    }
}
