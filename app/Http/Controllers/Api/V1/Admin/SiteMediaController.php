<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteMediaRequest;
use App\Http\Resources\SiteMediaResource;
use App\Models\SiteMedia;
use Illuminate\Http\JsonResponse;

/**
 * @group Admin - Appearance
 *
 * Manages the logo, hero banner, and footer ribbon slots shown on the storefront.
 * Slots are identified by a fixed `key` (see `config/site_media.php`) rather than
 * a numeric id, since there's always at most one row per slot.
 *
 * Requires the `super-admin` or `catalog-manager` role.
 *
 * @authenticated
 */
class SiteMediaController extends Controller
{
    /**
     * Update a media slot
     *
     * Accepts either an `image` file upload or an `image_url` pointing at an
     * externally hosted image (useful when no local file is on hand) — not
     * both. Sending a new image replaces and deletes the previous one, if it
     * was a locally uploaded file.
     */
    public function update(UpdateSiteMediaRequest $request, string $key): JsonResponse
    {
        $siteMedia = SiteMedia::firstOrCreate(['key' => $key]);

        $data = $request->safe()->except(['image', 'image_url']);

        if ($request->hasFile('image')) {
            $siteMedia->deleteStoredImage();
            $data['image_path'] = $request->file('image')->store('site-media', 'public');
        } elseif ($request->filled('image_url')) {
            $siteMedia->deleteStoredImage();
            $data['image_path'] = $request->string('image_url')->toString();
        }

        $siteMedia->update($data);

        return response()->json(['site_media' => new SiteMediaResource($siteMedia)]);
    }

    /**
     * Clear a media slot's image
     *
     * Resets the slot back to the frontend's default/fallback image without
     * touching its toggle or link URL.
     */
    public function destroyImage(string $key): JsonResponse
    {
        $siteMedia = SiteMedia::firstOrCreate(['key' => $key]);

        $siteMedia->deleteStoredImage();
        $siteMedia->update(['image_path' => null]);

        return response()->json(['site_media' => new SiteMediaResource($siteMedia)]);
    }
}
