<?php

namespace App\Filament\Resources\Attributes;

use App\Filament\Resources\Attributes\Pages\CreateAttribute;
use App\Filament\Resources\Attributes\Pages\EditAttribute;
use App\Filament\Resources\Attributes\Pages\ListAttributes;
use App\Filament\Resources\Attributes\Schemas\AttributeForm;
use App\Filament\Resources\Attributes\Tables\AttributesTable;
use App\Models\Attribute;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup =
        'المنتجات';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel =
        'الصفات';

    protected static ?string $modelLabel =
        'صفة';

    protected static ?string $pluralModelLabel =
        'الصفات';

    protected static ?string $recordTitleAttribute =
        'name';

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
        return AttributeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttributesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttributes::route('/'),
            'create' => CreateAttribute::route('/create'),
            'edit' => EditAttribute::route('/{record}/edit'),
        ];
    }
}