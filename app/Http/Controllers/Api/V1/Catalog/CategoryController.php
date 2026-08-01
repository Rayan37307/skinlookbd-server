<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Catalog
 */
class CategoryController extends Controller
{
    /**
     * List categories
     *
     * Returns active top-level categories with their active children nested. Pass `?featured=1`
     * to get only the categories curated for homepage display, ordered by their admin-configured
     * sort order.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::where('is_active', true)->whereNull('parent_id');

        if ($request->boolean('featured')) {
            $query->where('is_featured', true)->orderBy('sort_order')->orderBy('name');
        } else {
            $query->orderBy('name');
        }

        $categories = $query
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->get();

        return response()->json([
            'categories' => CategoryResource::collection($categories),
        ]);
    }
}
