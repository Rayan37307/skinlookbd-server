<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

/**
 * @group Catalog
 */
class CategoryController extends Controller
{
    /**
     * List categories
     *
     * Returns active top-level categories with their active children nested.
     */
    public function index(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return response()->json([
            'categories' => CategoryResource::collection($categories),
        ]);
    }
}
