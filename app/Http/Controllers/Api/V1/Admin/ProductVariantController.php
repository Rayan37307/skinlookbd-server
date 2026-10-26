<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;

/**
 * @group Admin - Catalog
 *
 * Requires the `super-admin` or `catalog-manager` role.
 *
 * @authenticated
 */
class ProductVariantController extends Controller
{
    /**
     * Add a variant to a product
     */
    public function store(StoreProductVariantRequest $request, Product $product): JsonResponse
    {
        $variant = $product->variants()->create($request->validated());

        return response()->json(['variant' => new ProductVariantResource($variant)], 201);
    }

    /**
     * Update a variant
     *
     * Stock quantity is not editable here — use the inventory adjustment endpoint
     * so every stock change is logged.
     */
    public function update(UpdateProductVariantRequest $request, ProductVariant $variant): JsonResponse
    {
        $variant->update($request->validated());

        return response()->json(['variant' => new ProductVariantResource($variant)]);
    }

    /**
     * Delete a variant
     *
     * Fails with a 422 while the variant is referenced by orders that can still
     * restock it; cancelled/returned orders do not block deletion.
     */
    public function destroy(ProductVariant $variant): JsonResponse
    {
        if ($variant->hasActiveOrders()) {
            abort(422, 'This variant cannot be deleted while it has active orders.');
        }

        $variant->delete();

        return response()->json(['message' => 'Variant deleted.']);
    }
}
