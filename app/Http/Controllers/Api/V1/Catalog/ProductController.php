<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ProductIndexRequest;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

/**
 * @group Catalog
 */
class ProductController extends Controller
{
    /**
     * List / search products
     *
     * Browse active products with filtering, search, sorting, and pagination.
     */
    public function index(ProductIndexRequest $request): JsonResponse
    {
        $query = Product::active()->with(['category.parent', 'brand', 'images', 'variants', 'tags', 'labels']);

        if ($category = $request->string('category')->value()) {
            $matchedCategory = Category::where('slug', $category)->first();

            $categoryIds = $matchedCategory
                ? Category::where('id', $matchedCategory->id)->orWhere('parent_id', $matchedCategory->id)->pluck('id')
                : collect();

            $query->whereIn('category_id', $categoryIds);
        }

        if ($subcategory = $request->string('subcategory')->value()) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $subcategory));
        }

        if ($skinType = $request->string('skin_type')->value()) {
            $query->whereHas('skinTypes', fn ($q) => $q->where('slug', $skinType));
        }

        if ($tag = $request->string('tag')->value()) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tag));
        }

        if ($concern = $request->string('concern')->value()) {
            $query->whereHas('concerns', fn ($q) => $q->where('slug', $concern));
        }

        if ($label = $request->string('label')->value()) {
            $query->whereHas('labels', fn ($q) => $q->where('slug', $label));
        }

        if ($brand = $request->string('brand')->value()) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $brand));
        }

        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', $request->integer('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->integer('max_price'));
        }

        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        if ($search = $request->string('search')->value()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$search}%")));
        }

        match ($request->string('sort')->value()) {
            'price_asc' => $query->orderBy('base_price'),
            'price_desc' => $query->orderByDesc('base_price'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderBy('name'),
        };

        $products = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'products' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Get product detail
     *
     * Returns full detail (images, variants, skin types, tags) for a single active product by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::active()
            ->with(['category.parent', 'brand', 'images', 'variants', 'skinTypes', 'tags', 'labels', 'relatedProducts.category.parent', 'relatedProducts.brand', 'relatedProducts.variants', 'relatedProducts.images'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'product' => new ProductDetailResource($product),
        ]);
    }
}
