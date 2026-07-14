<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

/**
 * @group Catalog
 */
class ProductReviewController extends Controller
{
    /**
     * List product reviews
     *
     * Returns only approved reviews for a product.
     */
    public function index(Product $product): JsonResponse
    {
        $reviews = $product->reviews()
            ->approved()
            ->with('user')
            ->latest()
            ->paginate(15);

        return response()->json([
            'reviews' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Submit a product review
     *
     * Requires the customer to have a non-cancelled order containing this product.
     * One review per product per customer; created with `pending` status pending
     * admin moderation.
     *
     * @authenticated
     */
    public function store(StoreReviewRequest $request, Product $product): JsonResponse
    {
        $user = $request->user();

        abort_if($product->reviews()->where('user_id', $user->id)->exists(), 422, 'You have already reviewed this product.');

        $orderItem = OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($query) => $query->where('user_id', $user->id)->where('status', '!=', 'cancelled'))
            ->first();

        abort_unless($orderItem, 422, 'You can only review products you have purchased.');

        $review = $product->reviews()->create([
            ...$request->validated(),
            'user_id' => $user->id,
            'order_item_id' => $orderItem->id,
            'status' => 'pending',
        ]);

        return response()->json(['review' => new ReviewResource($review)], 201);
    }
}
