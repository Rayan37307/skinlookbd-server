<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin - Customers
 *
 * Requires the `super-admin` or `order-manager` role.
 *
 * @authenticated
 */
class CustomerController extends Controller
{
    private const REVENUE_STATUSES = ['confirmed', 'processing', 'shipped', 'delivered'];

    /**
     * List customers
     *
     * Searchable by name/email/phone. Each entry includes order count and lifetime
     * value (sum of `confirmed`+ order totals).
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::role('customer')
            ->withCount('orders')
            ->withSum(['orders as lifetime_value' => fn ($q) => $q->whereIn('status', self::REVENUE_STATUSES)], 'total');

        if ($search = $request->string('search')->value()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }

        $customers = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'customers' => CustomerResource::collection($customers),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    /**
     * Get customer detail
     *
     * Profile, lifetime value, and full order history.
     */
    public function show(User $customer): JsonResponse
    {
        abort_unless($customer->hasRole('customer'), 404);

        $customer->loadCount('orders')
            ->loadSum(['orders as lifetime_value' => fn ($q) => $q->whereIn('status', self::REVENUE_STATUSES)], 'total');

        $orders = Order::where('user_id', $customer->id)->latest()->paginate(15);

        return response()->json([
            'customer' => new CustomerResource($customer),
            'orders' => OrderResource::collection($orders),
        ]);
    }
}
