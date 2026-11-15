<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

class HATCategoryChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 2;

    protected ?string $heading = 'توزيع المنتجات حسب التصنيف';

    protected ?string $description = 'عدد المنتجات المسجلة داخل كل تصنيف';

    protected function getData(): array
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderByDesc('products_count')
            ->get();

        return [
            'labels' => $categories
                ->pluck('name')
                ->values()
                ->all(),

            'datasets' => [
                [
                    'label' => 'المنتجات',
                    'data' => $categories
                        ->pluck('products_count')
                        ->values()
                        ->all(),
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}