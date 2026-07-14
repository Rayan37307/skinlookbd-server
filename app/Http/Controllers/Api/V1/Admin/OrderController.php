<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundOrderRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\AuditLog;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin - Orders
 *
 * Requires the `super-admin` or `order-manager` role.
 *
 * @authenticated
 */
class OrderController extends Controller
{
    private const array TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
        'returned' => [],
    ];

    public function __construct(private readonly OrderService $orders) {}

    /**
     * List orders
     *
     * Filterable by `status`, `user_id`, and a `from`/`to` date range.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['user', 'items']);

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($from = $request->string('from')->value()) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->string('to')->value()) {
            $query->whereDate('created_at', '<=', $to);
        }

        $orders = $query->latest()->paginate($request->integer('per_page', 15));

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
     * Update order status
     *
     * Enforces the lifecycle: `pending` → `confirmed` → `processing` → `shipped` →
     * `delivered`, with `cancelled` reachable from any pre-shipped state. Marking an
     * order `delivered` also marks a COD payment as `paid`. `returned` is only
     * reachable via the refund endpoint.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $newStatus = $request->string('status')->value();
        $previousStatus = $order->status;
        $allowed = self::TRANSITIONS[$previousStatus] ?? [];

        abort_unless(in_array($newStatus, $allowed, true), 422, "Cannot move an order from '{$previousStatus}' to '{$newStatus}'.");

        match ($newStatus) {
            'cancelled' => $this->orders->cancel($order, $request->user()),
            'delivered' => $this->orders->markDelivered($order),
            default => $order->update(['status' => $newStatus]),
        };

        AuditLog::record($request->user(), 'order.status_updated', $order, [
            'from' => $previousStatus,
            'to' => $newStatus,
        ]);

        return response()->json([
            'order' => new OrderResource($order->fresh('items')),
        ]);
    }

    /**
     * Refund an order
     *
     * Only allowed when the order has a `confirmed` or `paid` payment and is not
     * already `cancelled`/`returned`. Marks the payment `refunded`, restocks
     * inventory, and sets the order status to `returned`.
     */
    public function refund(RefundOrderRequest $request, Order $order): JsonResponse
    {
        abort_if(in_array($order->status, ['cancelled', 'returned'], true), 422, 'This order has already been cancelled or refunded.');
        abort_unless($order->payment && in_array($order->payment->status, ['confirmed', 'paid'], true), 422, 'This order has no confirmed payment to refund.');

        $this->orders->refund($order, $request->user(), $request->string('reason')->value() ?: null);

        AuditLog::record($request->user(), 'order.refunded', $order, [
            'reason' => $request->string('reason')->value() ?: null,
        ]);

        return response()->json([
            'order' => new OrderResource($order->fresh('items')),
        ]);
    }
}
