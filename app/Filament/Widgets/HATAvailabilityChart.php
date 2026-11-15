<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\ChartWidget;

class HATAvailabilityChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'حالة المنتجات';

    protected ?string $description = 'توزيع المنتجات حسب حالة التوفر';

    protected function getData(): array
    {
        $available = Product::query()
            ->where('availability', 'available')
            ->count();

        $unavailable = Product::query()
            ->where('availability', 'unavailable')
            ->count();

        return [
            'labels' => [
                'متوفر',
                'غير متوفر',
            ],

            'datasets' => [
                [
                    'label' => 'المنتجات',
                    'data' => [
                        $available,
                        $unavailable,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}