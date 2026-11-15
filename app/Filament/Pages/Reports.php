<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Product;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class Reports extends Page
{
    protected static ?string $title = 'التقارير';

    protected static ?string $navigationLabel = 'التقارير';

    protected static string|UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected string $view = 'filament.pages.reports';

    public string $availability = 'all';

    public string $activeStatus = 'all';

    public ?int $categoryId = null;
    
    public static function canAccess(): bool
{
    return auth()->user()?->can('reports.view') ?? false;
}

    public function getCategories()
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getProductsQuery(): Builder
    {
        return Product::query()
            ->with('category')
            ->when(
                $this->availability !== 'all',
                fn (Builder $query) =>
                    $query->where('availability', $this->availability)
            )
            ->when(
                $this->activeStatus !== 'all',
                fn (Builder $query) =>
                    $query->where('is_active', $this->activeStatus === 'active')
            )
            ->when(
                $this->categoryId,
                fn (Builder $query) =>
                    $query->where('category_id', $this->categoryId)
            )
            ->latest('created_at');
    }

    public function getProducts()
    {
        return $this->getProductsQuery()->get();
    }

    public function resetFilters(): void
    {
        $this->availability = 'all';
        $this->activeStatus = 'all';
        $this->categoryId = null;
    }

    public function getSummary(): array
    {
        $query = $this->getProductsQuery();

        return [
            'total' => (clone $query)->count(),

            'available' => (clone $query)
                ->where('availability', 'available')
                ->count(),

            'unavailable' => (clone $query)
                ->where('availability', 'unavailable')
                ->count(),

            'active' => (clone $query)
                ->where('is_active', true)
                ->count(),

            'uncategorized' => (clone $query)
                ->whereNull('category_id')
                ->count(),
        ];
    }
    public function exportPdf(): Response
{
    $products = $this->getProducts();
    $summary = $this->getSummary();

    $filters = [
        'availability' => match ($this->availability) {
            'available' => 'متوفر',
            'unavailable' => 'غير متوفر',
            default => 'الكل',
        },

        'activeStatus' => match ($this->activeStatus) {
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            default => 'الكل',
        },

        'category' => $this->categoryId
            ? optional(
                Category::find($this->categoryId)
            )->name ?? 'غير محدد'
            : 'الكل',
    ];

    $pdf = Pdf::loadView('filament.pages.reports-pdf', [
        'products' => $products,
        'summary' => $summary,
        'filters' => $filters,
    ])
        ->setPaper('a4', 'landscape');

    return $pdf->download(
        'HAT-Products-Report-' . now()->format('Y-m-d-H-i') . '.pdf'
    );
}

}