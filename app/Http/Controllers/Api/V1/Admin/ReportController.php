<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Admin - Reports
 *
 * Requires the `super-admin` or `order-manager` role.
 *
 * @authenticated
 */
class ReportController extends Controller
{
    /**
     * Export orders as CSV
     *
     * Streams a CSV of orders within a date range (`from`/`to`, default: last 30 days).
     */
    public function export(Request $request): StreamedResponse
    {
        $from = $request->date('from')?->startOfDay() ?? now()->subDays(29)->startOfDay();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        $orders = Order::with('user')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $filename = 'orders-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Order Number', 'Date', 'Customer Name', 'Customer Email', 'Status',
                'Payment Method', 'Subtotal', 'Discount', 'Shipping', 'Total',
            ]);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number,
                    $order->created_at->toDateTimeString(),
                    $order->user->name,
                    $order->user->email,
                    $order->status,
                    $order->payment_method,
                    $order->subtotal,
                    $order->discount_total,
                    $order->shipping_charge,
                    $order->total,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
