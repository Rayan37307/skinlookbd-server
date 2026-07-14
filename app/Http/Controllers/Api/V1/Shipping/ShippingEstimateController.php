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
     * Returns the shipping charge and ETA for a city/area, matched against configured
     * shipping zones or a flat default rate if no zone matches.
     */
    public function __invoke(ShippingEstimateRequest $request, ShippingService $shipping): JsonResponse
    {
        return response()->json(
            $shipping->calculate($request->string('city')->value(), $request->string('area')->value() ?: null)
        );
    }
}
