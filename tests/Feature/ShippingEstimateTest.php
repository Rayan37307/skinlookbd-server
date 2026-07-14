<?php

use App\Models\ShippingRate;
use App\Models\ShippingZone;

test('it returns the matching zone rate for a known city', function () {
    $zone = ShippingZone::factory()->create(['areas' => ['Dhaka']]);
    ShippingRate::factory()->for($zone, 'zone')->create(['charge' => 60, 'eta_days' => 2]);

    $response = $this->getJson('/api/v1/shipping/estimate?city=Dhaka');

    $response->assertOk()
        ->assertJson(['charge' => 60, 'eta_days' => 2]);
});

test('it falls back to the default charge for an unmatched city', function () {
    $response = $this->getJson('/api/v1/shipping/estimate?city=Somewhere+Remote');

    $response->assertOk()->assertJsonStructure(['charge', 'eta_days']);
});
