<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('item_no')
                    ->label('رقم الصنف')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('اسم المنتج')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير مصنف')
                    ->badge(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('barcode')
                    ->label('الباركود')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('availability')
                    ->label('التوفر')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'available' => 'متوفر',
                            'unavailable' => 'غير متوفر',
                            default => $state ?? 'غير محدد',
                        }
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'available' => 'success',
                            'unavailable' => 'danger',
                            default => 'gray',
                        }
                    ),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('المحذوفات'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف'),

                    ForceDeleteBulkAction::make()
                        ->label('حذف نهائي'),

                    RestoreBulkAction::make()
                        ->label('استعادة'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}