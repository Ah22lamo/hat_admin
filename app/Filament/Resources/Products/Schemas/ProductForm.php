<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\AttributeValue;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make("بيانات المنتج")
                ->description("المعلومات الأساسية للمنتج")
                ->schema([
                    TextInput::make("name")
                        ->label("اسم المنتج")
                        ->required()
                        ->maxLength(255),

                    TextInput::make("item_no")
                        ->label("رقم الصنف")
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    TextInput::make("sku")
                        ->label("SKU")
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make("barcode")
                        ->label("الباركود")
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Select::make("category_id")
                        ->label("التصنيف")
                        ->relationship("category", "name")
                        ->searchable()
                        ->preload()
                        ->required()
                        ->placeholder("اختر التصنيف"),

                    Textarea::make("description")
                        ->label("الوصف")
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make("خصائص المنتج")
                ->description("اختر القيم المناسبة للمنتج مثل اللون والمقاس والخامة.")
                ->schema([
                    Select::make("attributeValues")
                        ->label("خصائص المنتج")
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(function (): array {
                            return AttributeValue::query()
                                ->with("attribute")
                                ->whereHas("attribute", function ($query): void {
                                    $query->where("is_active", true);
                                })
                                ->orderBy("attribute_id")
                                ->orderBy("value")
                                ->get()
                                ->groupBy(function (AttributeValue $attributeValue): string {
                                    return $attributeValue->attribute->name;
                                })
                                ->map(function ($values): array {
                                    return $values
                                        ->mapWithKeys(function (AttributeValue $attributeValue): array {
                                            return [
                                                $attributeValue->id => $attributeValue->value,
                                            ];
                                        })
                                        ->toArray();
                                })
                                ->toArray();
                        })
                        ->placeholder("اختر قيم الخصائص")
                        ->helperText("يمكنك اختيار أكثر من قيمة من مختلف الصفات.")
                        ->columnSpanFull(),
                ])
                ->columns(1),

            Section::make("صور المنتج")
                ->description("أضف صورة رئيسية وصورًا إضافية للمنتج.")
                ->schema([
                    Repeater::make("images")
                        ->label("صور المنتج")
                        ->relationship("images")
                        ->orderColumn("sort_order")
                        ->defaultItems(0)
                        ->addActionLabel("إضافة صورة")
                        ->reorderable(true)
                        ->collapsible()
                        ->itemLabel(function (array $state): ?string {
                            return $state["alt_text"]
                                ?? $state["path"]
                                ?? "صورة جديدة";
                        })
                        ->schema([
                            FileUpload::make("path")
                                ->label("صورة المنتج")
                                ->disk("public")
                                ->directory("products")
                                ->visibility("public")
                                ->image()
                                ->imagePreviewHeight("180")
                                ->previewable(true)
                                ->downloadable(false)
                                ->required(false)
                                ->maxSize(10240)
                                ->helperText("يمكنك استبدال الصورة باختيار صورة جديدة."),

                            TextInput::make("alt_text")
                                ->label("وصف الصورة")
                                ->placeholder("مثال: لعبة أطفال باللون الأحمر")
                                ->maxLength(255),

                            Toggle::make("is_primary")
                                ->label("الصورة الرئيسية")
                                ->default(false),

                            TextInput::make("source")
                                ->label("مصدر الصورة")
                                ->default("manual")
                                ->maxLength(255),

                            TextInput::make("sort_order")
                                ->label("ترتيب الصورة")
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columns(1),

            Section::make("حالة المنتج")
                ->description("إعدادات التوفر والنشاط")
                ->schema([
                    Select::make("availability")
                        ->label("التوفر")
                        ->options([
                            "available" => "متوفر",
                            "unavailable" => "غير متوفر",
                        ])
                        ->default("available")
                        ->required(),

                    Toggle::make("is_active")
                        ->label("المنتج نشط")
                        ->default(true)
                        ->inline(false),
                ])
                ->columns(2),
        ]);
    }
}
