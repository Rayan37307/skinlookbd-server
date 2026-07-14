<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Http\Requests\Admin\UpdateBannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * @group Admin - Promotions
 *
 * Requires the `super-admin` or `catalog-manager` role.
 *
 * @authenticated
 */
class BannerController extends Controller
{
    /**
     * List all banners
     *
     * Unlike the public endpoint, returns every banner regardless of active state
     * or date window.
     */
    public function index(): JsonResponse
    {
        $banners = Banner::orderBy('sort_order')->get();

        return response()->json([
            'banners' => BannerResource::collection($banners),
        ]);
    }

    /**
     * Create a banner
     */
    public function store(StoreBannerRequest $request): JsonResponse
    {
        $banner = Banner::create([
            ...$request->safe()->except('image'),
            'image' => $request->file('image')->store('banners', 'public'),
        ]);

        return response()->json(['banner' => new BannerResource($banner)], 201);
    }

    /**
     * Update a banner
     *
     * Sending a new `image` file replaces and deletes the old one.
     */
    public function update(UpdateBannerRequest $request, Banner $banner): JsonResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($banner->image);
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);

        return response()->json(['banner' => new BannerResource($banner)]);
    }

    /**
     * Delete a banner
     */
    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return response()->json(['message' => 'Banner deleted.']);
    }
}
