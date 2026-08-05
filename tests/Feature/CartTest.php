<?php

use App\Models\Cart;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\QueryException;

test('a guest can add an item to their cart and receives a cart token', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $response = $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $response->assertOk();
    $token = $response->headers->get('X-Cart-Token');

    expect($token)->not->toBeNull();
    expect($response->json('cart.items.0.quantity'))->toBe(2);
});

test('a guest can remove a cart item using the cart token', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $addResponse = $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);
    $token = $addResponse->headers->get('X-Cart-Token');
    $itemId = $addResponse->json('cart.items.0.id');

    $removeResponse = $this->withHeader('X-Cart-Token', $token)->deleteJson("/api/v1/cart/items/{$itemId}");

    $removeResponse->assertOk();
    expect($removeResponse->json('cart.items'))->toHaveCount(0);
});

test('removing a guest cart item without the matching cart token 404s', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $addResponse = $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);
    $itemId = $addResponse->json('cart.items.0.id');

    // No X-Cart-Token header sent — simulates a lost/cleared/mismatched guest token, which
    // resolves to a brand-new empty cart rather than the one the item actually belongs to.
    $this->deleteJson("/api/v1/cart/items/{$itemId}")->assertNotFound();
});

test('a guest cart persists across requests using the cart token', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $first = $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);
    $token = $first->headers->get('X-Cart-Token');

    $response = $this->withHeader('X-Cart-Token', $token)->getJson('/api/v1/cart');

    $response->assertOk();
    expect($response->json('cart.items'))->toHaveCount(1);
});

test('adding more items than available stock fails', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 2]);

    $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 3,
    ])->assertUnprocessable();
});

test("an authenticated user's cart is tied to their account, not a token", function () {
    actingAsUser();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ])->assertOk();

    expect(Cart::count())->toBe(1);
    expect(Cart::first()->user_id)->not->toBeNull();
});

test('a user can update a cart item quantity', function () {
    actingAsUser();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);
    $cartItemId = Cart::first()->items()->first()->id;

    $response = $this->patchJson("/api/v1/cart/items/{$cartItemId}", ['quantity' => 5]);

    $response->assertOk();
    expect($response->json('cart.items.0.quantity'))->toBe(5);
});

test('a user can remove a cart item', function () {
    actingAsUser();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);
    $cartItemId = Cart::first()->items()->first()->id;

    $response = $this->deleteJson("/api/v1/cart/items/{$cartItemId}");

    $response->assertOk();
    expect($response->json('cart.items'))->toHaveCount(0);
});

test('after a guest cart merges into a user on login, the account cart still works with just the auth token', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $guestResponse = $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);
    $staleGuestToken = $guestResponse->headers->get('X-Cart-Token');

    $loginResponse = $this->withHeader('X-Cart-Token', $staleGuestToken)->postJson('/api/v1/auth/login', [
        'login' => $user->email,
        'password' => 'password123',
    ]);
    $authToken = $loginResponse->json('token');

    // A client that (bug) still sends the now-stale guest token alongside the auth token, e.g.
    // because it was never cleared after login — the auth token must take priority so the
    // request resolves to the real account cart, not a nonexistent/mismatched guest one.
    $cartResponse = $this->withHeader('Authorization', "Bearer {$authToken}")
        ->withHeader('X-Cart-Token', $staleGuestToken)
        ->getJson('/api/v1/cart');

    $cartResponse->assertOk();
    expect($cartResponse->json('cart.items'))->toHaveCount(1);
    $mergedItemId = $cartResponse->json('cart.items.0.id');

    $removeResponse = $this->withHeader('Authorization', "Bearer {$authToken}")
        ->withHeader('X-Cart-Token', $staleGuestToken)
        ->deleteJson("/api/v1/cart/items/{$mergedItemId}");

    $removeResponse->assertOk();
    expect($removeResponse->json('cart.items'))->toHaveCount(0);
});

test('a user never ends up with two cart rows even if firstOrCreate races', function () {
    $user = User::factory()->create();

    Cart::factory()->for($user)->create();

    // Simulate a second concurrent request finding no cart yet and trying to create one too.
    expect(fn () => Cart::create(['user_id' => $user->id]))->toThrow(QueryException::class);
    expect(Cart::where('user_id', $user->id)->count())->toBe(1);
});

test("a user cannot modify another user's cart item", function () {
    $other = User::factory()->create();
    $otherCart = Cart::factory()->for($other)->create();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $item = $otherCart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

    actingAsUser();

    $this->patchJson("/api/v1/cart/items/{$item->id}", ['quantity' => 2])->assertNotFound();
});

test('a guest cart is merged into the user cart on login', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $guestResponse = $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);
    $token = $guestResponse->headers->get('X-Cart-Token');

    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/auth/login', [
        'login' => $user->email,
        'password' => 'password123',
    ])->assertOk();

    $userCart = Cart::where('user_id', $user->id)->first();

    expect($userCart)->not->toBeNull();
    expect($userCart->items()->sum('quantity'))->toBe(2);
    expect(Cart::whereNull('user_id')->count())->toBe(0);
});
