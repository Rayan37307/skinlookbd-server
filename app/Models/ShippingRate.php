<?php

namespace App\Models;

use Database\Factories\ShippingRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['charge', 'eta_days'])]
class ShippingRate extends Model
{
    /** @use HasFactory<ShippingRateFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ShippingZone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
