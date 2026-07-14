<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\AdminProductResource;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Admin - Catalog
 *
 * Requires the `super-admin` or `catalog-manager` role.
 *
 * @authenticated
 */
class ProductController extends Controller
{
    /**
     * List all products
     *
     * Unlike the public endpoint, returns products of any status (draft/active/archived).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'images', 'variants']);

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($search = $request->string('search')->value()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%"));
        }

        $products = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'products' => AdminProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Create a product
     *
     * Created with `draft` status by default unless `status` is given.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create([
            ...$request->safe()->except('skin_type_ids'),
            'slug' => $request->string('slug')->value() ?: Str::slug($request->string('name')->value()),
            'status' => $request->string('status')->value() ?: 'draft',
        ]);

        if ($request->has('skin_type_ids')) {
            $product->skinTypes()->sync($request->input('skin_type_ids'));
        }

        return response()->json([
            'product' => new AdminProductResource($product->load('category', 'images', 'variants', 'skinTypes')),
        ], 201);
    }

    /**
     * Get product detail
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'product' => new AdminProductResource($product->load('category', 'images', 'variants', 'skinTypes')),
        ]);
    }

    /**
     * Update a product
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->safe()->except('skin_type_ids'));

        if ($request->has('skin_type_ids')) {
            $product->skinTypes()->sync($request->input('skin_type_ids'));
        }

        return response()->json([
            'product' => new AdminProductResource($product->load('category', 'images', 'variants', 'skinTypes')),
        ]);
    }

    /**
     * Delete a product
     *
     * Fails with a 422 if the product has existing orders.
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            $product->delete();
        } catch (QueryException) {
            abort(422, 'This product cannot be deleted while it has existing orders.');
        }

        return response()->json(['message' => 'Product deleted.']);
    }
}
