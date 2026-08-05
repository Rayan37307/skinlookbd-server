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

test('a guest can remove a cart item by product_variant_id using the cart token', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $addResponse = $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);
    $token = $addResponse->headers->get('X-Cart-Token');

    $removeResponse = $this->withHeader('X-Cart-Token', $token)
        ->deleteJson("/api/v1/cart/items/{$variant->id}");

    $removeResponse->assertOk();
    expect($removeResponse->json('cart.items'))->toHaveCount(0);
});

test('removing a variant that is not in the cart is a harmless no-op, not an error', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $addResponse = $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);
    $token = $addResponse->headers->get('X-Cart-Token');

    $otherVariant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    // No matching row for $otherVariant in this cart (or, without the token, resolves to a
    // brand-new empty cart) — either way the caller's goal ("it's not in my cart") is already
    // true, so this succeeds instead of 404ing.
    $response = $this->withHeader('X-Cart-Token', $token)
        ->deleteJson("/api/v1/cart/items/{$otherVariant->id}");

    $response->assertOk();
    expect($response->json('cart.items'))->toHaveCount(1);
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

test('a user can update a cart item quantity by product_variant_id', function () {
    actingAsUser();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    $response = $this->patchJson("/api/v1/cart/items/{$variant->id}", ['quantity' => 5]);

    $response->assertOk();
    expect($response->json('cart.items.0.quantity'))->toBe(5);
});

test('updating a variant that is not in the cart 404s', function () {
    actingAsUser();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->patchJson("/api/v1/cart/items/{$variant->id}", ['quantity' => 2])->assertNotFound();
});

test('a user can remove a cart item by product_variant_id', function () {
    actingAsUser();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    $response = $this->deleteJson("/api/v1/cart/items/{$variant->id}");

    $response->assertOk();
    expect($response->json('cart.items'))->toHaveCount(0);
});

test("a user cannot remove or update another user's cart item, because it's simply not reachable", function () {
    $other = User::factory()->create();
    $otherCart = Cart::factory()->for($other)->create();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $otherCart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

    actingAsUser();

    // The request always operates on the caller's own resolved cart — there's no id to smuggle
    // a reference to someone else's cart item through anymore.
    $this->patchJson("/api/v1/cart/items/{$variant->id}", ['quantity' => 2])->assertNotFound();
    $this->deleteJson("/api/v1/cart/items/{$variant->id}")->assertOk();

    expect($otherCart->fresh()->items()->count())->toBe(1);
});

test('after a guest cart merges into a user on login, removal still works with just the auth token', function () {
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
    // request resolves to the real account cart.
    $removeResponse = $this->withHeader('Authorization', "Bearer {$authToken}")
        ->withHeader('X-Cart-Token', $staleGuestToken)
        ->deleteJson("/api/v1/cart/items/{$variant->id}");

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

test('logging in with no pre-existing account cart adopts the guest cart, keeping item ids unchanged', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $guestResponse = $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);
    $token = $guestResponse->headers->get('X-Cart-Token');
    $guestCartId = $guestResponse->json('cart.id');
    $guestItemId = $guestResponse->json('cart.items.0.id');

    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/auth/login', [
        'login' => $user->email,
        'password' => 'password123',
    ])->assertOk();

    $userCart = Cart::where('user_id', $user->id)->first();

    // Same cart row, same item row — just re-owned, nothing recreated.
    expect($userCart->id)->toBe($guestCartId);
    expect($userCart->items()->first()->id)->toBe($guestItemId);
});

test('logging in with an existing account cart still merges the guest cart into it', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);
    $existingVariant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $guestVariant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $existingCart = Cart::factory()->for($user)->create();
    $existingCart->items()->create(['product_variant_id' => $existingVariant->id, 'quantity' => 1]);

    $guestResponse = $this->postJson('/api/v1/cart/items', [
        'product_variant_id' => $guestVariant->id,
        'quantity' => 1,
    ]);
    $token = $guestResponse->headers->get('X-Cart-Token');
    $guestCartId = $guestResponse->json('cart.id');

    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/auth/login', [
        'login' => $user->email,
        'password' => 'password123',
    ])->assertOk();

    expect(Cart::where('user_id', $user->id)->count())->toBe(1);
    expect(Cart::find($existingCart->id)->items()->count())->toBe(2);
    expect(Cart::find($guestCartId))->toBeNull();
});

test('adding the same variant twice increments quantity instead of creating a second row', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $first = $this->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);
    $token = $first->headers->get('X-Cart-Token');

    $response = $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    $response->assertOk();
    expect($response->json('cart.items'))->toHaveCount(1);
    expect($response->json('cart.items.0.quantity'))->toBe(2);
});
