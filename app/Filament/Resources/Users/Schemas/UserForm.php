<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم الموظف')
                    ->required()
                    ->maxLength(255),

                TextInput::make('username')
                    ->label('اسم المستخدم')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Select::make('roles')
                    ->label('الدور الوظيفي')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->maxItems(1)
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('اختر دورًا وظيفيًا واحدًا للموظف.'),

                TextInput::make('password')
                    ->label('كلمة المرور')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(8)
                    ->maxLength(255)
                    ->helperText(
                        fn (string $operation): ?string => $operation === 'edit'
                            ? 'اترك الحقل فارغًا إذا كنت لا تريد تغيير كلمة المرور.'
                            : 'يجب أن تكون كلمة المرور 8 أحرف على الأقل.'
                    ),

                Toggle::make('is_active')
    ->label('المستخدم نشط')
    ->default(true)
    ->inline(false)
    ->disabled(function (string $operation): bool {
        return $operation === 'edit'
            && auth()->id() === request()->route('record');
    })
    ->dehydrated()
    ->helperText('عند التعطيل، لن يُسمح للموظف باستخدام حسابه.')
    ->required(),
            ]);
    }
}