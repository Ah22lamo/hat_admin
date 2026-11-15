<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HATProductStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalProducts = Product::query()->count();

        $activeProducts = Product::query()
            ->where('is_active', true)
            ->count();

        $uncategorizedProducts = Product::query()
            ->whereNull('category_id')
            ->count();

        $unavailableProducts = Product::query()
            ->where('availability', 'unavailable')
            ->count();

        $activePercentage = $totalProducts > 0
            ? round(($activeProducts / $totalProducts) * 100)
            : 0;

        return [

            Stat::make(
                'إجمالي المنتجات',
                number_format($totalProducts)
            )
                ->description(
                    $totalProducts > 0
                        ? 'إجمالي المنتجات المسجلة'
                        : 'لم تتم إضافة منتجات بعد'
                )
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary')
                ->chart([
                    0,
                    0,
                    0,
                    0,
                    0,
                    $totalProducts,
                ]),

            Stat::make(
                'المنتجات النشطة',
                number_format($activeProducts)
            )
                ->description("تمثل {$activePercentage}% من إجمالي المنتجات")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(
                'غير مصنفة',
                number_format($uncategorizedProducts)
            )
                ->description(
                    $uncategorizedProducts > 0
                        ? 'تحتاج إلى تحديد التصنيف'
                        : 'جميع المنتجات مصنفة'
                )
                ->descriptionIcon('heroicon-m-tag')
                ->color(
                    $uncategorizedProducts > 0
                        ? 'warning'
                        : 'success'
                ),

            Stat::make(
                'غير متوفرة',
                number_format($unavailableProducts)
            )
                ->description(
                    $unavailableProducts > 0
                        ? 'تحتاج إلى مراجعة الحالة'
                        : 'جميع المنتجات متوفرة'
                )
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color(
                    $unavailableProducts > 0
                        ? 'danger'
                        : 'success'
                ),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}