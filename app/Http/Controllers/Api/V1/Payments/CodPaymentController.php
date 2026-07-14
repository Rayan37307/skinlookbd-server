<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\ConfirmCodRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

/**
 * @group Payments
 *
 * @authenticated
 */
class CodPaymentController extends Controller
{
    /**
     * Confirm a Cash-on-Delivery order
     *
     * Marks a pending COD order (and its payment record) as confirmed, typically
     * after a staff member has phoned the customer to verify the order. Restricted
     * to `super-admin` and `order-manager` roles.
     */
    public function confirm(ConfirmCodRequest $request): JsonResponse
    {
        $order = Order::with('payment', 'items')->findOrFail($request->integer('order_id'));

        abort_unless($order->payment_method === 'cod', 422, 'This order is not a COD order.');
        abort_unless($order->status === 'pending', 422, 'This order has already been processed.');

        $order->update(['status' => 'confirmed']);
        $order->payment?->update(['status' => 'confirmed']);

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }
}
