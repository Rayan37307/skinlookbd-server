<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
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
     */
    public function update(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        abort_unless($cartItem->cart_id === $cart->id, 404);

        abort_if($request->integer('quantity') > $cartItem->productVariant->stock_quantity, 422, 'Not enough stock available.');

        $cartItem->update(['quantity' => $request->integer('quantity')]);

        return $this->respondWithCart($cart);
    }

    /**
     * Remove a cart item
     */
    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        abort_unless($cartItem->cart_id === $cart->id, 404);

        $cartItem->delete();

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
