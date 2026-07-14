<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin - Dashboard
 *
 * Requires the `super-admin` or `order-manager` role. "Revenue" counts orders in
 * `confirmed`, `processing`, `shipped`, or `delivered` status only.
 *
 * @authenticated
 */
class DashboardController extends Controller
{
    private const REVENUE_STATUSES = ['confirmed', 'processing', 'shipped', 'delivered'];

    private const ALL_STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];

    private const LOW_STOCK_THRESHOLD = 5;

    /**
     * Summary
     *
     * Revenue totals for today/this week/this month, order counts by status, and
     * a low-stock variant count.
     */
    public function summary(Request $request): JsonResponse
    {
        $statusCounts = Order::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'revenue' => [
                'today' => $this->revenueSince(now()->startOfDay()),
                'week' => $this->revenueSince(now()->startOfWeek()),
                'month' => $this->revenueSince(now()->startOfMonth()),
            ],
            'orders_by_status' => collect(self::ALL_STATUSES)
                ->mapWithKeys(fn (string $status) => [$status => (int) ($statusCounts[$status] ?? 0)]),
            'low_stock_count' => ProductVariant::where('stock_quantity', '<=', $request->integer('low_stock_threshold', self::LOW_STOCK_THRESHOLD))->count(),
        ]);
    }

    /**
     * Sales time series
     *
     * Daily revenue and order counts over a date range (`from`/`to`, default: last
     * 30 days).
     */
    public function sales(Request $request): JsonResponse
    {
        $from = $request->date('from')?->startOfDay() ?? now()->subDays(29)->startOfDay();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        $rows = Order::whereIn('status', self::REVENUE_STATUSES)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'sales' => $rows->map(fn ($row) => [
                'date' => $row->date,
                'revenue' => (int) $row->revenue,
                'orders' => (int) $row->orders,
            ]),
        ]);
    }

    /**
     * Top products
     *
     * Best sellers by units sold over an optional date range, limited to `limit`
     * (default 10) results.
     */
    public function topProducts(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $from = $request->date('from')?->startOfDay();
        $to = $request->date('to')?->endOfDay();

        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', self::REVENUE_STATUSES)
            ->when($from, fn ($query) => $query->where('orders.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('orders.created_at', '<=', $to))
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as units_sold, SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.product_id')
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get();

        $products = Product::whereIn('id', $rows->pluck('product_id'))->get()->keyBy('id');

        return response()->json([
            'top_products' => $rows->map(fn ($row) => [
                'product_id' => $row->product_id,
                'name' => $products[$row->product_id]?->name,
                'slug' => $products[$row->product_id]?->slug,
                'units_sold' => (int) $row->units_sold,
                'revenue' => (int) $row->revenue,
            ]),
        ]);
    }

    private function revenueSince(Carbon $since): int
    {
        return (int) Order::whereIn('status', self::REVENUE_STATUSES)
            ->where('created_at', '>=', $since)
            ->sum('total');
    }
}
