<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInventoryAdjustmentRequest;
use App\Models\InventoryLog;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @group Admin - Inventory
 *
 * Requires the `super-admin` or `catalog-manager` role.
 *
 * @authenticated
 */
class InventoryAdjustmentController extends Controller
{
    /**
     * Adjust stock
     *
     * Applies a positive or negative quantity change to a variant's stock and
     * records an `InventoryLog` entry. Rejected with a 422 if it would push stock
     * below zero.
     */
    public function store(StoreInventoryAdjustmentRequest $request): JsonResponse
    {
        $variant = ProductVariant::findOrFail($request->integer('product_variant_id'));
        $changeQuantity = $request->integer('change_quantity');

        abort_if($variant->stock_quantity + $changeQuantity < 0, 422, 'This adjustment would result in negative stock.');

        DB::transaction(function () use ($request, $variant, $changeQuantity) {
            $variant->increment('stock_quantity', $changeQuantity);

            $log = new InventoryLog([
                'change_quantity' => $changeQuantity,
                'reason' => $request->string('reason')->value(),
                'note' => $request->string('note')->value() ?: null,
                'created_by' => $request->user()->id,
            ]);
            $log->productVariant()->associate($variant);
            $log->save();
        });

        return response()->json([
            'variant' => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'stock_quantity' => $variant->fresh()->stock_quantity,
            ],
        ]);
    }
}
