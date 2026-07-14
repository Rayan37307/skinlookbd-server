<?php

namespace App\Http\Controllers\Api\V1\Wishlist;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Wishlist
 *
 * @authenticated
 */
class WishlistController extends Controller
{
    /**
     * List my wishlist
     */
    public function index(Request $request): JsonResponse
    {
        $wishlists = $request->user()->wishlists()->with('product')->latest()->get();

        return response()->json([
            'wishlist' => WishlistResource::collection($wishlists),
        ]);
    }

    /**
     * Add a product to the wishlist
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['product_id' => ['required', 'integer', 'exists:products,id']]);

        $user = $request->user();

        abort_if($user->wishlists()->where('product_id', $request->integer('product_id'))->exists(), 422, 'This product is already in your wishlist.');

        $wishlist = $user->wishlists()->create(['product_id' => $request->integer('product_id')]);

        return response()->json(['wishlist' => new WishlistResource($wishlist->load('product'))], 201);
    }

    /**
     * Remove a product from the wishlist
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $request->user()->wishlists()->where('product_id', $product->id)->delete();

        return response()->json(['message' => 'Removed from wishlist.']);
    }
}
