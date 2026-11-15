<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\HATProductStats;
use App\Filament\Widgets\HATCategoryChart;
use App\Filament\Widgets\HATAvailabilityChart;
use App\Filament\Widgets\HATProductDataHealth;
use App\Filament\Widgets\HATProductAttention;
use App\Filament\Widgets\HATImportActivity;
use App\Filament\Widgets\HATOrderStats;
use App\Filament\Widgets\HATOrderStatusChart;
use App\Filament\Widgets\HATOrderTrend;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')

            ->maxContentWidth(Width::Full)
            ->sidebarWidth('17rem')
            ->sidebarCollapsibleOnDesktop()

            ->login()

            ->brandName('HAT Admin')
            ->brandLogo(asset('images/hat-logo.png'))

            ->colors([
                'primary' => Color::hex('#238B95'),
            ])

            ->viteTheme('resources/css/filament/admin/theme.css')

            /*
             * Navigation groups
             *
             * ترتيب مجموعات الـSidebar.
             */
            ->navigationGroups([
                'المنتجات',
                'المبيعات',
                'التقارير',
                'الإدارة',
            ])

            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources'
            )

            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages'
            )

            ->pages([
                Dashboard::class,
            ])

            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\\Filament\\Widgets'
            )

            ->widgets([
                HATProductStats::class,
    HATOrderStats::class,

    HATOrderStatusChart::class,
    HATOrderTrend::class,

    HATCategoryChart::class,
    HATAvailabilityChart::class,

    HATProductDataHealth::class,
    HATProductAttention::class,
    HATImportActivity::class,

    AccountWidget::class,
            ])

            /*
             * Quick Actions
             *
             * هذه عناصر فعلية فقط، وليست نسخًا من Resources.
             * لا نضع "المنتجات" هنا لأنها موجودة أصلًا في Resources.
             */
            ->navigationItems([
                NavigationItem::make('إضافة منتج')
                    ->group('الوصول السريع')
                    ->icon('heroicon-o-plus-circle')
                    ->url(fn (): string => route(
                        'filament.admin.resources.products.create'
                    ))
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can('products.create') ?? false
                    )
                    ->sort(1),

                NavigationItem::make('التقارير')
                    ->group('الوصول السريع')
                    ->icon('heroicon-o-chart-bar-square')
                    ->url(
                        fn (): string =>
                            \App\Filament\Pages\Reports::getUrl()
                    )
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can('reports.view') ?? false
                    )
                    ->sort(2),
            ])

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

