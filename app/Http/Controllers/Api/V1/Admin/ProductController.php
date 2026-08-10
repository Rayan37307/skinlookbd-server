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
    private const WITH = ['category.parent', 'brand', 'images', 'variants', 'skinTypes', 'tags', 'labels', 'relatedProducts'];

    /**
     * List all products
     *
     * Unlike the public endpoint, returns products of any status (draft/active/archived).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(self::WITH);

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId = $request->integer('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($search = $request->string('search')->value()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$search}%")));
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
            ...$request->safe()->except(['skin_type_ids', 'tag_ids', 'label_ids', 'related_product_ids']),
            'slug' => $request->string('slug')->value() ?: Str::slug($request->string('name')->value()),
            'status' => $request->string('status')->value() ?: 'draft',
        ]);

        $this->syncRelations($request, $product);

        if ($product->variants()->count() === 0) {
            $product->variants()->create([
                'sku' => ($product->sku ?: 'SLB-'.str_pad((string) $product->id, 4, '0', STR_PAD_LEFT)).'-STD',
                'size_label' => 'Standard',
                'price_override' => null,
                'stock_quantity' => $product->stock_quantity ?? 0,
            ]);
        }

        return response()->json([
            'product' => new AdminProductResource($product->load(self::WITH)),
        ], 201);
    }

    /**
     * Get product detail
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'product' => new AdminProductResource($product->load(self::WITH)),
        ]);
    }

    /**
     * Update a product
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->safe()->except(['skin_type_ids', 'tag_ids', 'label_ids', 'related_product_ids']));

        $this->syncRelations($request, $product);

        return response()->json([
            'product' => new AdminProductResource($product->load(self::WITH)),
        ]);
    }

    /**
     * @param  StoreProductRequest|UpdateProductRequest  $request
     */
    protected function syncRelations($request, Product $product): void
    {
        if ($request->has('skin_type_ids')) {
            $product->skinTypes()->sync($request->input('skin_type_ids'));
        }

        if ($request->has('tag_ids')) {
            $product->tags()->sync($request->input('tag_ids'));
        }

        if ($request->has('label_ids')) {
            $product->labels()->sync($request->input('label_ids'));
        }

        if ($request->has('related_product_ids')) {
            $product->relatedProducts()->sync($request->input('related_product_ids'));
        }
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
