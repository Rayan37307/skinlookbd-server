<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Revenue (last 30 days)';

    private const REVENUE_STATUSES = ['confirmed', 'processing', 'shipped', 'delivered'];

    protected function getData(): array
    {
        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        $rows = Order::whereIn('status', self::REVENUE_STATUSES)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->groupBy('date')
            ->pluck('revenue', 'date');

        $labels = [];
        $data = [];

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $data[] = (int) ($rows[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (BDT)',
                    'data' => $data,
                    'borderColor' => '#10b981',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
