<?php

namespace App\Filament\Resources\AttributeValues;

use App\Filament\Resources\AttributeValues\Pages\CreateAttributeValue;
use App\Filament\Resources\AttributeValues\Pages\EditAttributeValue;
use App\Filament\Resources\AttributeValues\Pages\ListAttributeValues;
use App\Filament\Resources\AttributeValues\Schemas\AttributeValueForm;
use App\Filament\Resources\AttributeValues\Tables\AttributeValuesTable;
use App\Models\AttributeValue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AttributeValueResource extends Resource
{
    protected static ?string $model = AttributeValue::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup =
        'المنتجات';

    protected static ?int $navigationSort = 31;

    protected static ?string $navigationLabel =
        'قيم الصفات';

    protected static ?string $modelLabel =
        'قيمة صفة';

    protected static ?string $pluralModelLabel =
        'قيم الصفات';

    protected static ?string $recordTitleAttribute =
        'value';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('attributes.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('attributes.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('attributes.update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('attributes.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('attributes.delete') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return AttributeValueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttributeValuesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttributeValues::route('/'),
            'create' => CreateAttributeValue::route('/create'),
            'edit' => EditAttributeValue::route('/{record}/edit'),
        ];
    }
}

