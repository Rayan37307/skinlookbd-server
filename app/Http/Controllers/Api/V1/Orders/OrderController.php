<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Orders
 *
 * @authenticated
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    /**
     * List my orders
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()
            ->with('items')
            ->latest()
            ->paginate(15);

        return response()->json([
            'orders' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Get order detail
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return response()->json([
            'order' => new OrderResource($order->load('items')),
        ]);
    }

    /**
     * Cancel an order
     *
     * Only allowed while the order is `pending` or `confirmed`. Restocks the
     * reserved inventory.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        abort_unless($order->isCancellable(), 422, 'This order can no longer be cancelled.');

        $this->orders->cancel($order, $request->user());

        return response()->json([
            'order' => new OrderResource($order->fresh('items')),
        ]);
    }
}
