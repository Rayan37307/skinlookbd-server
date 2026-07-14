<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * @group Admin - Catalog
 *
 * Requires the `super-admin` or `catalog-manager` role.
 *
 * @authenticated
 */
class CategoryController extends Controller
{
    /**
     * List all categories
     *
     * Unlike the public endpoint, returns every category regardless of active state.
     */
    public function index(): JsonResponse
    {
        $categories = Category::with('children')->orderBy('name')->get();

        return response()->json([
            'categories' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Create a category
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create([
            ...$request->validated(),
            'slug' => $request->string('slug')->value() ?: Str::slug($request->string('name')->value()),
        ]);

        return response()->json(['category' => new CategoryResource($category)], 201);
    }

    /**
     * Update a category
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        abort_if((int) $request->input('parent_id') === $category->id, 422, 'A category cannot be its own parent.');

        $category->update($request->validated());

        return response()->json(['category' => new CategoryResource($category)]);
    }

    /**
     * Delete a category
     *
     * Fails with a 422 if the category still has products assigned to it.
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            $category->delete();
        } catch (QueryException) {
            abort(422, 'This category cannot be deleted while it still has products.');
        }

        return response()->json(['message' => 'Category deleted.']);
    }
}
