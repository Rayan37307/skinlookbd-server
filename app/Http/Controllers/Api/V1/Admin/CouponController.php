<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;

/**
 * @group Admin - Promotions
 *
 * Requires the `super-admin` or `catalog-manager` role.
 *
 * @authenticated
 */
class CouponController extends Controller
{
    /**
     * List coupons
     */
    public function index(): JsonResponse
    {
        $coupons = Coupon::latest()->paginate(15);

        return response()->json([
            'coupons' => CouponResource::collection($coupons),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'total' => $coupons->total(),
            ],
        ]);
    }

    /**
     * Create a coupon
     *
     * The code is normalized to uppercase.
     */
    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = Coupon::create([
            ...$request->validated(),
            'code' => $request->string('code')->upper()->value(),
        ]);

        return response()->json(['coupon' => new CouponResource($coupon)], 201);
    }

    /**
     * Update a coupon
     */
    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        $coupon->update([
            ...$request->validated(),
            ...($request->has('code') ? ['code' => $request->string('code')->upper()->value()] : []),
        ]);

        return response()->json(['coupon' => new CouponResource($coupon)]);
    }

    /**
     * Delete a coupon
     */
    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted.']);
    }
}
