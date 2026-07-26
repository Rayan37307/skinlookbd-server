<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;

/**
 * @group Catalog
 */
class MenuController extends Controller
{
    /**
     * Get the navigation menu
     *
     * Returns active menu items as a nested tree, sorted by `sort_order`, mirroring the
     * shape of the categories endpoint (each node has a `children` array, to any depth).
     */
    public function index(): JsonResponse
    {
        $items = Menu::where('is_active', true)->orderBy('sort_order')->get();
        $byParent = $items->groupBy('parent_id');

        foreach ($items as $item) {
            $item->setRelation('children', $byParent->get($item->id, collect()));
        }

        $roots = $byParent->get(null, collect());

        return response()->json([
            'menu' => MenuResource::collection($roots),
        ]);
    }
}
