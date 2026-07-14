<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductImagesRequest;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;

/**
 * @group Admin - Catalog
 *
 * Requires the `super-admin` or `catalog-manager` role.
 *
 * @authenticated
 */
class ProductImageController extends Controller
{
    /**
     * Upload product images
     *
     * Accepts multiple images in a single request; each is stored on the `public`
     * disk and appended to the product's gallery in order.
     */
    public function store(StoreProductImagesRequest $request, Product $product): JsonResponse
    {
        $nextSortOrder = (int) $product->images()->max('sort_order') + 1;

        $images = collect($request->file('images'))->map(function ($file, $index) use ($product, $nextSortOrder) {
            return $product->images()->create([
                'path' => $file->store('products', 'public'),
                'alt' => $product->name,
                'sort_order' => $nextSortOrder + $index,
            ]);
        });

        return response()->json([
            'images' => $images->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'path' => $image->path,
                'alt' => $image->alt,
                'sort_order' => $image->sort_order,
            ]),
        ], 201);
    }

    /**
     * Delete a product image
     */
    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        $image->delete();

        return response()->json(['message' => 'Image deleted.']);
    }
}
