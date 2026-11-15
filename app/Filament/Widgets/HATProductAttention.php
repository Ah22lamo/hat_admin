<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class HATProductAttention extends TableWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('مركز المراجعة')
            ->description('المنتجات التي تحتاج إلى متابعة أو استكمال بياناتها')

            ->query(
                fn (): Builder => Product::query()
                    ->where(function (Builder $query) {
                        $query
                            ->whereNull('category_id')
                            ->orWhereNull('sku')
                            ->orWhere('sku', '')
                            ->orWhere('availability', 'unavailable');
                    })
                    ->with('category')
                    ->latest('updated_at')
            )

            ->columns([
                TextColumn::make('item_no')
                    ->label('رقم الصنف')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('اسم المنتج')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->placeholder('غير مصنف')
                    ->badge(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->placeholder('غير موجود'),

                TextColumn::make('availability')
                    ->label('التوفر')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'available' => 'متوفر',
                            'unavailable' => 'غير متوفر',
                            default => 'غير محدد',
                        }
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'available' => 'success',
                            'unavailable' => 'danger',
                            default => 'gray',
                        }
                    ),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])

            ->emptyStateHeading('لا توجد منتجات تحتاج إلى مراجعة')
            ->emptyStateDescription(
                'عند وجود منتجات ناقصة البيانات أو غير متوفرة ستظهر هنا.'
            )
            ->emptyStateIcon('heroicon-o-check-circle')

            ->paginated([5, 10, 25]);
    }
}