<?php

use App\Models\Review;

test('a customer cannot access admin reviews', function () {
    actingAsUser();

    $this->getJson('/api/v1/admin/reviews')->assertForbidden();
});

test('a catalog manager can filter reviews by status', function () {
    actingAsAdmin('catalog-manager');
    Review::factory()->pending()->create();
    Review::factory()->create(['status' => 'approved']);

    $response = $this->getJson('/api/v1/admin/reviews?status=pending');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

test('a catalog manager can approve a pending review', function () {
    actingAsAdmin('catalog-manager');
    $review = Review::factory()->pending()->create();

    $response = $this->patchJson("/api/v1/admin/reviews/{$review->id}/status", ['status' => 'approved']);

    $response->assertOk()->assertJsonPath('review.status', 'approved');
});
