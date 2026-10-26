<?php

namespace App\Http\Controllers\Api\V1\Shipping;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shipping\ShippingEstimateRequest;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;

/**
 * @group Shipping
 */
class ShippingEstimateController extends Controller
{
    /**
     * Estimate shipping charge
     *
     * Returns the charge, ETA, and free-delivery threshold for a city, using the delivery zones
     * configured in the admin panel (falling back to the built-in tiers for cities no zone
     * covers). The city must match one of the whitelisted values.
     *
     * Pass the cart `subtotal` to have the quote account for free delivery: the returned
     * `charge` is 0 when the subtotal reaches the zone's threshold, and `is_free_delivery`
     * says which branch was taken.
     */
    public function __invoke(ShippingEstimateRequest $request, ShippingService $shipping): JsonResponse
    {
        return response()->json(
            $shipping->calculate(
                $request->string('city')->value(),
                $request->filled('subtotal') ? $request->integer('subtotal') : null,
            )
        );
    }
}
