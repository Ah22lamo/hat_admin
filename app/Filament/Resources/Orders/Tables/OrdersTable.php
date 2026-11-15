<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.phone')
                    ->label('الهاتف')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('customer.customer_type')
                    ->label('نوع العميل')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'visitor' => 'زائر',
                        'customer' => 'عميل',
                        'wholesale' => 'عميل جملة',
                        default => 'غير محدد',
                    }),

                TextColumn::make('status')
                    ->label('حالة الطلب')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'gray',
                        'confirmed' => 'info',
                        'processing' => 'warning',
                        'ready' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'confirmed' => 'مؤكد',
                        'processing' => 'قيد المعالجة',
                        'ready' => 'جاهز',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغى',
                        default => 'غير محدد',
                    }),

                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->suffix(' ﷼')
                    ->sortable(),

                TextColumn::make('payments.status')
                    ->label('الدفع')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'pending' => 'بانتظار التحقق',
                        'verified' => 'تم التحقق',
                        'rejected' => 'مرفوض',
                        default => 'لا يوجد',
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->label('حالة الطلب')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'confirmed' => 'مؤكد',
                        'processing' => 'قيد المعالجة',
                        'ready' => 'جاهز',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغى',
                    ]),

                SelectFilter::make('customer.customer_type')
                    ->label('نوع العميل')
                    ->options([
                        'visitor' => 'زائر',
                        'customer' => 'عميل',
                        'wholesale' => 'عميل جملة',
                    ]),

                SelectFilter::make('payments.status')
                    ->label('حالة الدفع')
                    ->options([
                        'pending' => 'بانتظار التحقق',
                        'verified' => 'تم التحقق',
                        'rejected' => 'مرفوض',
                    ]),
            ])

            ->recordActions([
                EditAction::make()
                    ->label('إدارة'),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])

            ->defaultSort('created_at', 'desc');
    }
}