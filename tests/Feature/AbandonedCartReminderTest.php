<?php

use App\Models\Cart;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\AbandonedCartReminder;
use Illuminate\Support\Facades\Notification;

test('an inactive cart with items gets a reminder', function () {
    Notification::fake();

    $user = User::factory()->create();
    $cart = Cart::factory()->for($user)->create();
    $cart->items()->create([
        'product_variant_id' => ProductVariant::factory()->create()->id,
        'quantity' => 1,
    ]);
    $cart->timestamps = false;
    $cart->forceFill(['updated_at' => now()->subHours(2)])->save();

    $this->artisan('carts:send-abandoned-reminders')->assertSuccessful();

    Notification::assertSentTo($user, AbandonedCartReminder::class);
    expect($cart->fresh()->reminder_count)->toBe(1);
});

test('a recently active cart is not reminded yet', function () {
    Notification::fake();

    $user = User::factory()->create();
    $cart = Cart::factory()->for($user)->create();
    $cart->items()->create([
        'product_variant_id' => ProductVariant::factory()->create()->id,
        'quantity' => 1,
    ]);

    $this->artisan('carts:send-abandoned-reminders');

    Notification::assertNothingSent();
});

test('an empty cart is never reminded', function () {
    Notification::fake();

    $user = User::factory()->create();
    Cart::factory()->for($user)->create();

    $this->artisan('carts:send-abandoned-reminders');

    Notification::assertNothingSent();
});

test('a guest cart with no user is never reminded', function () {
    Notification::fake();

    $cart = Cart::factory()->create(['user_id' => null, 'session_token' => 'guest-token']);
    $cart->items()->create([
        'product_variant_id' => ProductVariant::factory()->create()->id,
        'quantity' => 1,
    ]);

    $this->artisan('carts:send-abandoned-reminders');

    Notification::assertNothingSent();
});

test('a cart already at the max reminder count is not reminded again', function () {
    Notification::fake();

    $user = User::factory()->create();
    $cart = Cart::factory()->for($user)->create([
        'reminder_count' => 3,
        'last_reminder_sent_at' => now()->subDays(10),
    ]);
    $cart->items()->create([
        'product_variant_id' => ProductVariant::factory()->create()->id,
        'quantity' => 1,
    ]);

    $this->artisan('carts:send-abandoned-reminders');

    Notification::assertNothingSent();
});

test('a cart that already received its first reminder waits for the next step', function () {
    Notification::fake();

    $user = User::factory()->create();
    $cart = Cart::factory()->for($user)->create([
        'reminder_count' => 1,
        'last_reminder_sent_at' => now()->subHours(2),
    ]);
    $cart->items()->create([
        'product_variant_id' => ProductVariant::factory()->create()->id,
        'quantity' => 1,
    ]);

    $this->artisan('carts:send-abandoned-reminders');

    Notification::assertNothingSent();
    expect($cart->fresh()->reminder_count)->toBe(1);
});
