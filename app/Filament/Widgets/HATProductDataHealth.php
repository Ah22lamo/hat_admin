<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HATProductDataHealth extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $total = Product::query()->count();

        $withCategory = Product::query()
            ->whereNotNull('category_id')
            ->count();

        $withSku = Product::query()
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->count();

        $withBarcode = Product::query()
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->count();

        $withImages = Product::query()
            ->whereHas('images')
            ->count();

        $percentage = function (int $value) use ($total): string {
            if ($total === 0) {
                return '0%';
            }

            return round(($value / $total) * 100) . '%';
        };

        return [
            Stat::make(
                'التصنيف',
                $percentage($withCategory)
            )
                ->description("{$withCategory} من {$total} منتج")
                ->descriptionIcon('heroicon-m-tag')
                ->color($withCategory === $total ? 'success' : 'warning'),

            Stat::make(
                'SKU',
                $percentage($withSku)
            )
                ->description("{$withSku} من {$total} منتج")
                ->descriptionIcon('heroicon-m-qr-code')
                ->color($withSku === $total ? 'success' : 'warning'),


            Stat::make(
                'الصور',
                $percentage($withImages)
            )
                ->description("{$withImages} من {$total} منتج")
                ->descriptionIcon('heroicon-m-photo')
                ->color($withImages === $total ? 'success' : 'warning'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}