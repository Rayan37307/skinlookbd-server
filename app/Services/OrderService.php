<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function cancel(Order $order, User $actor): void
    {
        DB::transaction(function () use ($order, $actor) {
            $this->restockItems($order, $actor, 'return');
            $order->update(['status' => 'cancelled']);
        });
    }

    public function refund(Order $order, User $actor, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $actor, $reason) {
            if (! in_array($order->status, ['cancelled', 'returned'], true)) {
                $this->restockItems($order, $actor, 'return', $reason);
            }

            $order->payment?->update(['status' => 'refunded']);
            $order->update(['status' => 'returned']);
        });
    }

    public function markDelivered(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'delivered']);

            if ($order->payment_method === 'cod') {
                $order->payment?->update(['status' => 'paid', 'paid_at' => now()]);
            }
        });
    }

    private function restockItems(Order $order, User $actor, string $reason, ?string $note = null): void
    {
        foreach ($order->items()->with('productVariant')->get() as $item) {
            $item->productVariant->increment('stock_quantity', $item->quantity);

            $log = new InventoryLog([
                'change_quantity' => $item->quantity,
                'reason' => $reason,
                'note' => $note,
                'created_by' => $actor->id,
            ]);
            $log->productVariant()->associate($item->productVariant);
            $log->reference()->associate($item);
            $log->save();
        }
    }
}
