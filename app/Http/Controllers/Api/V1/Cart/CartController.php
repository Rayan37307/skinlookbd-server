<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Cart
 *
 * Works for both guests and authenticated customers. Guests are tracked via an
 * `X-Cart-Token` header: send it back on every request once you've received it
 * from a response, and it merges into the customer's cart automatically on login.
 */
class CartController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    /**
     * View cart
     */
    public function show(Request $request): JsonResponse
    {
        $cart = $this->carts->resolve($request);

        return $this->respondWithCart($cart);
    }

    /**
     * Add an item to the cart
     */
    public function store(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        $variant = ProductVariant::findOrFail($request->integer('product_variant_id'));

        $item = $cart->items()->where('product_variant_id', $variant->id)->first();
        $desiredQuantity = ($item?->quantity ?? 0) + $request->integer('quantity');

        abort_if($desiredQuantity > $variant->stock_quantity, 422, 'Not enough stock available.');

        if ($item) {
            $item->update(['quantity' => $desiredQuantity]);
        } else {
            $cart->items()->create([
                'product_variant_id' => $variant->id,
                'quantity' => $desiredQuantity,
            ]);
        }

        return $this->respondWithCart($cart);
    }

    /**
     * Update a cart item's quantity
     *
     * Identifies the item by `product_variant_id` in the URL rather than a cart-item id, since
     * a cart can only ever hold one row per variant — the frontend never needs to track or
     * reuse an internal row id that could otherwise go stale (e.g. across a guest-to-account
     * merge).
     */
    public function update(UpdateCartItemRequest $request, ProductVariant $productVariant): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        $item = $cart->items()->where('product_variant_id', $productVariant->id)->firstOrFail();

        abort_if($request->integer('quantity') > $productVariant->stock_quantity, 422, 'Not enough stock available.');

        $item->update(['quantity' => $request->integer('quantity')]);

        return $this->respondWithCart($cart);
    }

    /**
     * Remove a cart item
     *
     * Identified by `product_variant_id` in the URL (see update()) — deliberately not a request
     * body, since some servers/PHP setups don't reliably deliver a body on DELETE requests, and
     * a URL segment has no such ambiguity. Removing a variant that isn't in the cart is a
     * no-op, not an error: the caller's goal ("this variant isn't in my cart") is already true.
     */
    public function destroy(Request $request, ProductVariant $productVariant): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        $cart->items()->where('product_variant_id', $productVariant->id)->delete();

        return $this->respondWithCart($cart);
    }

    private function respondWithCart(Cart $cart): JsonResponse
    {
        $cart->load('items.productVariant.product.images');

        $response = response()->json(['cart' => new CartResource($cart)]);

        if (! $cart->user_id) {
            $response->headers->set('X-Cart-Token', $cart->session_token);
        }

        return $response;
    }
}
