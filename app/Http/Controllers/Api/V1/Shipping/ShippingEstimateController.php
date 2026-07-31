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
     * Returns the shipping charge and ETA for a city, looked up against a fixed 3-tier
     * pricing table (Dhaka / Dhaka Suburb / other Bangladesh districts). The city must
     * exactly match one of the whitelisted values.
     */
    public function __invoke(ShippingEstimateRequest $request, ShippingService $shipping): JsonResponse
    {
        return response()->json(
            $shipping->calculate($request->string('city')->value())
        );
    }
}
