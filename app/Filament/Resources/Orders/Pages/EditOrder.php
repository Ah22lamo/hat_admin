<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف الطلب')
                ->requiresConfirmation()
                ->modalHeading('حذف الطلب')
                ->modalDescription(
                    'سيتم حذف الطلب والبيانات التابعة له وفق علاقات قاعدة البيانات.'
                ),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث الطلب بنجاح';
    }
}