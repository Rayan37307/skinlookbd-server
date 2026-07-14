<?php

use App\Models\User;

test('a user can register', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '01712345678',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.email', 'jane@example.com')
        ->assertJsonStructure(['user', 'token']);

    $user = User::where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('customer'))->toBeTrue();
});

test('registration fails with a duplicate phone number', function () {
    User::factory()->create(['phone' => '01712345678']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane2@example.com',
        'phone' => '01712345678',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('phone');
});

test('a user can log in with email or phone', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'phone' => '01712345678',
        'password' => bcrypt('password123'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'login' => 'jane@example.com',
        'password' => 'password123',
    ])->assertOk()->assertJsonStructure(['user', 'token']);

    $this->postJson('/api/v1/auth/login', [
        'login' => $user->phone,
        'password' => 'password123',
    ])->assertOk()->assertJsonStructure(['user', 'token']);
});

test('login fails with an incorrect password', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'login' => 'jane@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('login');
});

test('an authenticated user can log out', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout');

    $response->assertOk();
    expect($user->tokens()->count())->toBe(0);
});
