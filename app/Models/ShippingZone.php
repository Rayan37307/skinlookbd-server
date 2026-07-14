<?php

namespace App\Models;

use Database\Factories\ShippingZoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'areas'])]
class ShippingZone extends Model
{
    /** @use HasFactory<ShippingZoneFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'areas' => 'array',
        ];
    }

    /**
     * @return HasOne<ShippingRate, $this>
     */
    public function rate(): HasOne
    {
        return $this->hasOne(ShippingRate::class);
    }

    public function matches(string $city, ?string $area = null): bool
    {
        $needles = array_filter([mb_strtolower($city), $area ? mb_strtolower($area) : null]);

        return collect($this->areas)
            ->map(fn (string $value) => mb_strtolower($value))
            ->intersect($needles)
            ->isNotEmpty();
    }
}
