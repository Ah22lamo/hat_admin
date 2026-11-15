<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class HATOrderTrend extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 2;

    protected ?string $heading = 'اتجاه الطلبات';

    protected ?string $description = 'عدد الطلبات خلال آخر 14 يوماً';

    protected function getData(): array
    {
        $start = Carbon::today()->subDays(13);

        $orders = Order::query()
            ->whereDate('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as order_date, COUNT(*) as total')
            ->groupBy('order_date')
            ->pluck('total', 'order_date');

        $labels = [];
        $data = [];

        for ($i = 0; $i < 14; $i++) {
            $date = $start->copy()->addDays($i);

            $key = $date->format('Y-m-d');

            $labels[] = $date->format('m/d');
            $data[] = (int) ($orders[$key] ?? 0);
        }

        return [
            'labels' => $labels,

            'datasets' => [
                [
                    'label' => 'الطلبات',
                    'data' => $data,
                    'fill' => true,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}