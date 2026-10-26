<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * @group Admin - Promotions
 *
 * Requires the `super-admin` or `catalog-manager` role.
 *
 * Coupons can be restricted to specific products, categories, brands and/or tags via
 * `product_ids`, `category_ids`, `brand_ids` and `tag_ids`. Selections are OR-ed together
 * and, once any is set, only matching cart items are discounted. Omitting them leaves the
 * scope untouched on update; sending an empty array clears it.
 *
 * `method` is `code` (default, customers enter `code`) or `automatic` (applied at checkout
 * without a code; code is stored as null). `discount_type` also accepts `free_delivery`,
 * which waives the shipping charge.
 *
 * @authenticated
 */
class CouponController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const SCOPES = [
        'products' => 'product_ids',
        'categories' => 'category_ids',
        'brands' => 'brand_ids',
        'tags' => 'tag_ids',
    ];

    /**
     * List coupons
     */
    public function index(): JsonResponse
    {
        $coupons = Coupon::with(['products:id,name', 'categories:id,name', 'brands:id,name', 'tags:id,name'])
            ->latest()
            ->paginate(15);

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
        $coupon = DB::transaction(function () use ($request): Coupon {
            $data = Arr::except($request->validated(), array_values(self::SCOPES));

            $data['method'] = $data['method'] ?? 'code';
            $data['code'] = $data['method'] === 'automatic'
                ? null
                : $request->string('code')->upper()->value();

            if ($data['discount_type'] === 'free_delivery') {
                $data['discount_value'] = 0;
                $data['max_discount'] = null;
            }

            $coupon = Coupon::create($data);

            $this->syncScopes($coupon, $request);

            return $coupon;
        });

        return response()->json(['coupon' => new CouponResource($coupon->load(array_keys(self::SCOPES)))], 201);
    }

    /**
     * Update a coupon
     */
    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        DB::transaction(function () use ($request, $coupon): void {
            $data = Arr::except($request->validated(), array_values(self::SCOPES));

            if (array_key_exists('method', $data) || $request->has('code')) {
                $method = $data['method'] ?? $coupon->method;

                $data['code'] = $method === 'automatic'
                    ? null
                    : ($request->has('code')
                        ? $request->string('code')->upper()->value()
                        : $coupon->code);
            }

            if (($data['discount_type'] ?? $coupon->discount_type) === 'free_delivery') {
                $data['discount_value'] = 0;
                $data['max_discount'] = null;
            }

            $coupon->update($data);

            $this->syncScopes($coupon, $request);
        });

        return response()->json(['coupon' => new CouponResource($coupon->load(array_keys(self::SCOPES)))]);
    }

    /**
     * Delete a coupon
     */
    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted.']);
    }

    private function syncScopes(Coupon $coupon, Request $request): void
    {
        foreach (self::SCOPES as $relation => $key) {
            if ($request->has($key)) {
                $coupon->{$relation}()->sync($request->input($key, []));
            }
        }
    }
}
