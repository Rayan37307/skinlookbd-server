<?php

use App\Models\Address;
use App\Models\User;

test('guests cannot access addresses', function () {
    $this->getJson('/api/v1/me/addresses')->assertUnauthorized();
});

test('a user can list only their own addresses', function () {
    $user = actingAsUser();
    Address::factory()->for($user)->count(2)->create();
    Address::factory()->create();

    $response = $this->getJson('/api/v1/me/addresses');

    $response->assertOk();
    expect($response->json('addresses'))->toHaveCount(2);
});

test('a user can create an address', function () {
    actingAsUser();

    $response = $this->postJson('/api/v1/me/addresses', [
        'recipient_name' => 'Jane Doe',
        'phone' => '01712345678',
        'address_line1' => 'House 1, Road 2',
        'city' => 'Dhaka',
        'type' => 'shipping',
    ]);

    $response->assertCreated()->assertJsonPath('address.city', 'Dhaka');
    expect(Address::count())->toBe(1);
});

test("a user cannot update another user's address", function () {
    $owner = User::factory()->create();
    $address = Address::factory()->for($owner)->create();

    actingAsUser();

    $this->patchJson("/api/v1/me/addresses/{$address->id}", [
        'city' => 'Chittagong',
    ])->assertForbidden();
});

test('a user can delete their own address', function () {
    $user = actingAsUser();
    $address = Address::factory()->for($user)->create();

    $this->deleteJson("/api/v1/me/addresses/{$address->id}")->assertOk();

    expect(Address::find($address->id))->toBeNull();
});
