<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueOverview extends StatsOverviewWidget
{
    private const REVENUE_STATUSES = ['confirmed', 'processing', 'shipped', 'delivered'];

    protected function getStats(): array
    {
        return [
            Stat::make('Revenue today', '৳'.number_format($this->revenueSince(now()->startOfDay())))
                ->color('success'),
            Stat::make('Revenue this week', '৳'.number_format($this->revenueSince(now()->startOfWeek())))
                ->color('success'),
            Stat::make('Revenue this month', '৳'.number_format($this->revenueSince(now()->startOfMonth())))
                ->color('success'),
            Stat::make('Pending orders', Order::where('status', 'pending')->count())
                ->color('gray'),
            Stat::make('Low stock variants', ProductVariant::where('stock_quantity', '<=', 5)->count())
                ->color('warning'),
        ];
    }

    private function revenueSince(Carbon $since): int
    {
        return (int) Order::whereIn('status', self::REVENUE_STATUSES)
            ->where('created_at', '>=', $since)
            ->sum('total');
    }
}
