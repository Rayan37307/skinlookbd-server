<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConcernResource;
use App\Models\Concern;
use Illuminate\Http\JsonResponse;

/**
 * @group Catalog
 */
class ConcernController extends Controller
{
    /**
     * List concerns
     *
     * Returns active skin-concern tiles (e.g. "Acne Care", "Dry Skin") for the "Shop by
     * Concern" homepage section, ordered by their admin-configured sort order.
     */
    public function index(): JsonResponse
    {
        $concerns = Concern::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'concerns' => ConcernResource::collection($concerns),
        ]);
    }
}
