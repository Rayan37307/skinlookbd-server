<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartService
{
    public function resolve(Request $request): Cart
    {
        if ($user = $request->user('sanctum')) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
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

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

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
}
