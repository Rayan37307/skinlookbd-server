<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteMediaResource;
use App\Models\SiteMedia;
use Illuminate\Http\JsonResponse;

/**
 * @group Catalog
 */
class SiteMediaController extends Controller
{
    /**
     * List site media slots
     *
     * Returns every known slot (logo, hero banner, footer ribbon, ...) — including
     * ones that have never been customized, so the frontend can always fall back
     * to its own static default when `image_url` is null or `is_active` is false.
     */
    public function index(): JsonResponse
    {
        $existing = SiteMedia::whereIn('key', array_keys(config('site_media.slots')))
            ->get()
            ->keyBy('key');

        $slots = collect(config('site_media.slots'))
            ->map(fn (array $config, string $key) => $existing->get($key) ?? new SiteMedia([
                'key' => $key,
                'image_path' => null,
                'link_url' => null,
                'is_active' => true,
            ]))
            ->values();

        return response()->json([
            'site_media' => SiteMediaResource::collection($slots),
        ]);
    }
}
