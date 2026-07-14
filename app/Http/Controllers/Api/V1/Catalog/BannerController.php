<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;

/**
 * @group Catalog
 */
class BannerController extends Controller
{
    /**
     * List homepage banners
     *
     * Returns active banners currently within their start/expiry window, ordered
     * by sort order.
     */
    public function index(): JsonResponse
    {
        $banners = Banner::live()->orderBy('sort_order')->get();

        return response()->json([
            'banners' => BannerResource::collection($banners),
        ]);
    }
}
