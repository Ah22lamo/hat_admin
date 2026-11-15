<?php

namespace App\Filament\Resources\Attributes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم الصفة')
                    ->placeholder('مثال: اللون، المقاس، الخامة')
                    ->required()
                    ->maxLength(255)
                    ->autofocus()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (
                        ?string $state,
                        callable $set,
                    ): void {
                        if (blank($state)) {
                            return;
                        }

                        $set(
                            'slug',
                            Str::slug($state)
                        );
                    }),

                TextInput::make('slug')
                    ->label('المعرّف')
                    ->placeholder('يتم إنشاؤه تلقائيًا')
                    ->helperText('يُستخدم داخليًا لتمييز الصفة. يمكنك تعديله عند الحاجة.')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        table: 'attributes',
                        column: 'slug',
                        ignoreRecord: true,
                    ),

                Toggle::make('is_active')
                    ->label('الصفة نشطة')
                    ->default(true)
                    ->inline(false),
            ]);
    }
}


