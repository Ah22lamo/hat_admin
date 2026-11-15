<?php

namespace App\Filament\Widgets;

use App\Models\ImportBatch;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class HATImportActivity extends TableWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('سجل عمليات الاستيراد')
            ->description('آخر عمليات استيراد بيانات المنتجات إلى النظام')
            ->query(
                fn (): Builder => ImportBatch::query()
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('file_name')
                    ->label('الملف')
                    ->searchable()
                    ->limit(35)
                    ->weight('bold'),

                TextColumn::make('import_type')
                    ->label('نوع الاستيراد')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'products' => 'المنتجات',
                            'images' => 'الصور',
                            'categories' => 'التصنيفات',
                            default => $state ?? 'غير محدد',
                        }
                    ),

                TextColumn::make('total_rows')
                    ->label('إجمالي الصفوف')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('successful_rows')
                    ->label('ناجح')
                    ->numeric()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('failed_rows')
                    ->label('فاشل')
                    ->numeric()
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'pending' => 'قيد الانتظار',
                            'processing' => 'جارٍ التنفيذ',
                            'completed' => 'مكتمل',
                            'completed_with_errors' => 'مكتمل مع أخطاء',
                            'failed' => 'فشل',
                            default => $state ?? 'غير محدد',
                        }
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'completed' => 'success',
                            'completed_with_errors' => 'warning',
                            'processing' => 'info',
                            'failed' => 'danger',
                            'pending' => 'gray',
                            default => 'gray',
                        }
                    ),

                TextColumn::make('completed_at')
                    ->label('تاريخ الإكمال')
                    ->dateTime('Y/m/d H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->emptyStateHeading('لا توجد عمليات استيراد حتى الآن')
            ->emptyStateDescription(
                'ستظهر هنا جميع عمليات استيراد المنتجات والتصنيفات والصور.'
            )
            ->emptyStateIcon('heroicon-o-arrow-up-tray')
            ->paginated([5, 10, 25]);
    }
}