<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات التصنيف')
                    ->schema([
                       TextInput::make('name')
    ->label('اسم التصنيف')
    ->required()
    ->maxLength(255)
    ->live(onBlur: true)
    ->afterStateUpdated(function ($state, $set) {
        $set('slug', Str::slug($state));
    })
    ->placeholder('أدخل اسم التصنيف'),
 TextInput::make('category_code')
    ->label('رمز التصنيف')
    ->required()
    ->unique(ignoreRecord: true)
    ->maxLength(20)
    ->regex('/^CAT_\d{2}$/')
    ->placeholder('CAT_01')
    ->helperText('يجب أن يكون بالشكل CAT_01 أو CAT_02 ...'),
TextInput::make('slug')
    ->label('الرابط المختصر')
    ->required()
    ->unique(ignoreRecord: true)
    ->disabled()
    ->dehydrated(),
                        Select::make('parent_id')
                            ->label('التصنيف الأب')
                            ->placeholder('تصنيف رئيسي')
                            ->options(fn () => Category::query()
                                ->whereNull('parent_id')
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),

                        Textarea::make('description')
                            ->label('الوصف')
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('وصف اختياري للتصنيف'),
                    ])
                    ->columns(2),

                Section::make('صورة التصنيف')
                    ->schema([
                        FileUpload::make('image')
                            ->label('صورة التصنيف')
                            ->image()
                            ->disk('public')
                            ->directory('categories')
                            ->imageEditor()
                            ->maxSize(2048),
                    ]),

                Section::make('إعدادات التصنيف')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('إذا كان غير نشط فلن يظهر كتصنيف متاح للعميل.'),

                        TextInput::make('sort_order')
                            ->label('ترتيب العرض')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }
}