<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class HATOrderStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'حالات الطلبات';

    protected ?string $description = 'توزيع الطلبات الحالية حسب الحالة';

    protected function getData(): array
    {
        $statuses = [
            'pending' => 'قيد الانتظار',
            'confirmed' => 'مؤكد',
            'processing' => 'قيد المعالجة',
            'ready' => 'جاهز',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغى',
        ];

        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => array_values($statuses),

            'datasets' => [
                [
                    'label' => 'الطلبات',
                    'data' => array_map(
                        fn (string $status): int =>
                            (int) ($counts[$status] ?? 0),
                        array_keys($statuses)
                    ),
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}