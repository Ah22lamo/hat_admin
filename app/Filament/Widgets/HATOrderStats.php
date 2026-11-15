<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HATOrderStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $total = Order::query()->count();

        $pending = Order::query()
            ->where('status', 'pending')
            ->count();

        $processing = Order::query()
            ->where('status', 'processing')
            ->count();

        $completed = Order::query()
            ->where('status', 'completed')
            ->count();

        $cancelled = Order::query()
            ->where('status', 'cancelled')
            ->count();

        return [
            Stat::make(
                'إجمالي الطلبات',
                number_format($total)
            )
                ->description('جميع الطلبات المسجلة')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make(
                'قيد الانتظار',
                number_format($pending)
            )
                ->description(
                    $pending > 0
                        ? 'طلبات تحتاج إلى متابعة'
                        : 'لا توجد طلبات معلقة'
                )
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'success'),

            Stat::make(
                'قيد المعالجة',
                number_format($processing)
            )
                ->description('طلبات قيد التنفيذ')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

            Stat::make(
                'مكتملة',
                number_format($completed)
            )
                ->description('طلبات مكتملة')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(
                'ملغاة',
                number_format($cancelled)
            )
                ->description(
                    $cancelled > 0
                        ? 'طلبات ملغاة'
                        : 'لا توجد طلبات ملغاة'
                )
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($cancelled > 0 ? 'danger' : 'success'),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }
}