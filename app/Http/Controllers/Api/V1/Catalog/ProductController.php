<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ProductIndexRequest;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
        $products = $this->buildQuery($request)->paginate($request->integer('per_page', 15));

        // MySQL FULLTEXT search silently drops words shorter than its minimum indexed word
        // length (and stopwords), which can turn a real match into a false empty result. When
        // that happens, retry once with a plain LIKE scan so correctness never depends on the
        // hosting box's ft_min_word_len/stopword configuration.
        if ($products->total() === 0 && $request->filled('search') && DB::connection()->getDriverName() === 'mysql') {
            $products = $this->buildQuery($request, forceLikeSearch: true)->paginate($request->integer('per_page', 15));
        }

        return response()->json([
            'products' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    private function buildQuery(ProductIndexRequest $request, bool $forceLikeSearch = false): Builder
    {
        $query = Product::active()->with(['categories.parent', 'brand', 'images', 'variants', 'tags', 'labels']);

        if ($category = $request->string('category')->value()) {
            $matchedCategory = Category::where('slug', $category)->first();

            $categoryIds = $matchedCategory
                ? Category::where('id', $matchedCategory->id)->orWhere('parent_id', $matchedCategory->id)->pluck('id')
                : collect();

            $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));
        }

        if ($subcategory = $request->string('subcategory')->value()) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $subcategory));
        }

        if ($skinType = $request->string('skin_type')->value()) {
            $query->whereHas('skinTypes', fn ($q) => $q->where('slug', $skinType));
        }

        if ($tag = $request->string('tag')->value()) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tag)->orWhere('name', $tag));
        }

        if ($concern = $request->string('concern')->value()) {
            $matchedConcern = Concern::where('slug', $concern)->first();

            if ($matchedConcern?->category_id) {
                $categoryIds = Category::where('id', $matchedConcern->category_id)
                    ->orWhere('parent_id', $matchedConcern->category_id)
                    ->pluck('id');

                $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));
            } elseif ($matchedConcern?->tag_id) {
                $query->whereHas('tags', fn ($q) => $q->where('id', $matchedConcern->tag_id));
            } else {
                $query->whereRaw('0 = 1');
            }
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
            $forceLikeSearch || DB::connection()->getDriverName() !== 'mysql'
                ? $this->applyLikeSearch($query, $search)
                : $this->applyFullTextSearch($query, $search);
        }

        match ($request->string('sort')->value()) {
            'price_asc' => $query->orderBy('base_price'),
            'price_desc' => $query->orderByDesc('base_price'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderBy('name'),
        };

        return $query;
    }

    /**
     * Index-accelerated search: MySQL FULLTEXT with boolean-mode prefix matching on each word
     * (e.g. "cera moist" -> +cera* +moist*), so results narrow as the user keeps typing without
     * ever falling back to a full table scan for the common case.
     */
    private function applyFullTextSearch(Builder $query, string $search): void
    {
        // Strip MySQL boolean-mode operators so user input can't be misread as query syntax.
        $words = array_filter(
            preg_split('/\s+/', trim(preg_replace('/[+\-<>()~*"@:]/', ' ', $search))),
            fn ($word) => $word !== '',
        );

        // Nothing left to match on (e.g. the search was pure punctuation) — LIKE is the only
        // option left, and MATCH AGAINST('') would otherwise error.
        if ($words === []) {
            $this->applyLikeSearch($query, $search);

            return;
        }

        $boolean = implode(' ', array_map(fn ($word) => '+'.$word.'*', $words));

        $query->where(function ($q) use ($boolean, $search) {
            $q->whereRaw('MATCH(name) AGAINST(? IN BOOLEAN MODE)', [$boolean])
                ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$search}%"));
        });
    }

    private function applyLikeSearch(Builder $query, string $search): void
    {
        $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
            ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$search}%")));
    }

    /**
     * Get product detail
     *
     * Returns full detail (images, variants, skin types, tags) for a single active product by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::active()
            ->with(['categories.parent', 'brand', 'images', 'variants', 'skinTypes', 'tags', 'labels', 'relatedProducts.categories.parent', 'relatedProducts.brand', 'relatedProducts.variants', 'relatedProducts.images'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'product' => new ProductDetailResource($product),
        ]);
    }
}
