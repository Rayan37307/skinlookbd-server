<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReviewStatusRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin - Reviews
 *
 * Requires the `super-admin` or `catalog-manager` role.
 *
 * @authenticated
 */
class ReviewController extends Controller
{
    /**
     * List reviews
     *
     * Filterable by `status` and `product_id`.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['user', 'product']);

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        if ($productId = $request->integer('product_id')) {
            $query->where('product_id', $productId);
        }

        $reviews = $query->latest()->paginate($request->integer('per_page', 15));

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
     * Approve or reject a review
     */
    public function updateStatus(UpdateReviewStatusRequest $request, Review $review): JsonResponse
    {
        $review->update(['status' => $request->string('status')->value()]);

        return response()->json(['review' => new ReviewResource($review)]);
    }
}
