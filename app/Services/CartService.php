<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartService
{
    public function resolve(Request $request): Cart
    {
        if ($user = $request->user('sanctum')) {
            return $this->cartForUser($user);
        }

        $token = $request->header('X-Cart-Token');

        if ($token && $cart = Cart::whereNull('user_id')->where('session_token', $token)->first()) {
            return $cart;
        }

        return Cart::create(['session_token' => (string) Str::uuid()]);
    }

    public function mergeGuestCartIntoUser(string $guestToken, User $user): void
    {
        $guestCart = Cart::whereNull('user_id')->where('session_token', $guestToken)->first();

        if (! $guestCart) {
            return;
        }

        $userCart = Cart::where('user_id', $user->id)->first();

        if (! $userCart) {
            // No pre-existing account cart to reconcile with — the guest cart just becomes the
            // account's cart. This keeps every item's id unchanged, which is what avoids the
            // whole class of "cart item vanished/404s after login" bugs: nothing was recreated.
            $guestCart->update(['user_id' => $user->id, 'session_token' => null]);

            return;
        }

        foreach ($guestCart->items as $guestItem) {
            $existing = $userCart->items()->where('product_variant_id', $guestItem->product_variant_id)->first();

            if ($existing) {
                $existing->increment('quantity', $guestItem->quantity);
            } else {
                $userCart->items()->create([
                    'product_variant_id' => $guestItem->product_variant_id,
                    'quantity' => $guestItem->quantity,
                ]);
            }
        }

        $guestCart->delete();
    }

    /**
     * firstOrCreate isn't atomic, so two near-simultaneous requests for a user's first-ever
     * cart (e.g. two open tabs) can both miss the SELECT and race to INSERT. The unique
     * constraint on carts.user_id stops that from creating a duplicate row; this just falls
     * back to fetching whichever request won the race instead of surfacing the conflict.
     */
    private function cartForUser(User $user): Cart
    {
        try {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        } catch (QueryException) {
            return Cart::where('user_id', $user->id)->firstOrFail();
        }
    }
}
