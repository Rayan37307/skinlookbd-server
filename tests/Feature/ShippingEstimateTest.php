<?php

test('it returns the Dhaka tier charge for the capital', function () {
    $response = $this->getJson('/api/v1/shipping/estimate?city=Dhaka');

    $response->assertOk()->assertJson(['charge' => 70, 'eta_days' => 2]);
});

test('it returns the suburb tier charge for a Dhaka suburb city', function () {
    $response = $this->getJson('/api/v1/shipping/estimate?city='.urlencode('Savar (Dhaka Suburb)'));

    $response->assertOk()->assertJson(['charge' => 100, 'eta_days' => 3]);
});

test('it returns the normal tier charge for another district', function () {
    $response = $this->getJson('/api/v1/shipping/estimate?city=Sylhet');

    $response->assertOk()->assertJson(['charge' => 130, 'eta_days' => 5]);
});

test('it rejects an unrecognized city rather than defaulting a tier', function () {
    $response = $this->getJson('/api/v1/shipping/estimate?city=Somewhere+Remote');

    $response->assertUnprocessable();
});
