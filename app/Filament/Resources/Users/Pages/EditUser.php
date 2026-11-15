<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (
            $this->record->id === auth()->id()
            && isset($data['is_active'])
        ) {
            $data['is_active'] = true;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف')
                ->visible(
                    fn (): bool =>
                        auth()->user()?->can('users.delete') === true
                        && auth()->id() !== $this->record->id
                ),
        ];
    }
}