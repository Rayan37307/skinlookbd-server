<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Catalog
 */
class BrandController extends Controller
{
    /**
     * List brands
     *
     * Returns active brands. Pass `?featured=1` to get only the brands curated for homepage
     * display, ordered by their admin-configured sort order.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Brand::where('is_active', true);

        if ($request->boolean('featured')) {
            $query->where('is_featured', true)->orderBy('sort_order')->orderBy('name');
        } else {
            $query->orderBy('name');
        }

        return response()->json([
            'brands' => BrandResource::collection($query->get()),
        ]);
    }
}
