<?php

use App\Models\Otp;
use App\Models\User;

test('an otp can be sent and verified', function () {
    $user = User::factory()->create(['phone' => '01712345678', 'phone_verified_at' => null]);

    $this->postJson('/api/v1/auth/otp/send', ['phone' => $user->phone])->assertOk();

    $otp = Otp::where('phone', $user->phone)->latest('id')->first();

    $this->postJson('/api/v1/auth/otp/verify', [
        'phone' => $user->phone,
        'code' => $otp->code,
    ])->assertOk();

    expect($user->fresh()->phone_verified_at)->not->toBeNull();
});

test('verifying with the wrong code fails', function () {
    $user = User::factory()->create(['phone' => '01712345678', 'phone_verified_at' => null]);

    $this->postJson('/api/v1/auth/otp/send', ['phone' => $user->phone])->assertOk();

    $this->postJson('/api/v1/auth/otp/verify', [
        'phone' => $user->phone,
        'code' => '000000',
    ])->assertUnprocessable();

    expect($user->fresh()->phone_verified_at)->toBeNull();
});

test('an expired otp cannot be verified', function () {
    $user = User::factory()->create(['phone' => '01712345678', 'phone_verified_at' => null]);

    $otp = Otp::create([
        'phone' => $user->phone,
        'code' => '123456',
        'purpose' => 'phone_verification',
        'expires_at' => now()->subMinute(),
    ]);

    $this->postJson('/api/v1/auth/otp/verify', [
        'phone' => $user->phone,
        'code' => $otp->code,
    ])->assertUnprocessable();
});
