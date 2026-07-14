<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin - Inventory
 *
 * Requires the `super-admin` or `catalog-manager` role.
 *
 * @authenticated
 */
class InventoryController extends Controller
{
    private const DEFAULT_THRESHOLD = 5;

    /**
     * Low-stock report
     *
     * Lists variants at or below a stock threshold (default 5), lowest first.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $threshold = $request->integer('threshold', self::DEFAULT_THRESHOLD);

        $variants = ProductVariant::with('product')
            ->where('stock_quantity', '<=', $threshold)
            ->orderBy('stock_quantity')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'variants' => $variants->getCollection()->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'size_label' => $variant->size_label,
                'stock_quantity' => $variant->stock_quantity,
                'product' => [
                    'id' => $variant->product->id,
                    'name' => $variant->product->name,
                    'slug' => $variant->product->slug,
                ],
            ])->values(),
            'meta' => [
                'current_page' => $variants->currentPage(),
                'last_page' => $variants->lastPage(),
                'total' => $variants->total(),
            ],
        ]);
    }
}
