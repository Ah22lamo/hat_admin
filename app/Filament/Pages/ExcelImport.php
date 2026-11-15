<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Services\ExcelImport\ExcelDatabaseImporter;
use App\Services\ExcelImport\ExcelImportEngine;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Livewire\WithFileUploads;
use Filament\Facades\Filament;
use UnitEnum;

class ExcelImport extends Page
{
    use WithFileUploads;

    protected static ?string $title = 'استيراد المنتجات';

    protected static ?string $navigationLabel = 'استيراد المنتجات';

    protected static string|UnitEnum|null $navigationGroup = 'المنتجات';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-arrow-up-tray';

    protected string $view = 'filament.pages.excel-import';

    public ?UploadedFile $file = null;

    public ?array $preview = null;

    public bool $analyzing = false;

    public bool $importing = false;

    public ?array $importResult = null;

   public static function canAccess(): bool
{
    return Filament::auth()->user()?->can('products.create') ?? false;
}

public function getCategoriesProperty(): array
{
    return Category::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->pluck('name', 'id')
        ->toArray();
}
    public function analyze(): void
    {
        $this->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:102000',
            ],
        ], [
            'file.required' => 'يرجى اختيار ملف Excel.',
            'file.file' => 'الملف المحدد غير صالح.',
            'file.mimes' => 'يسمح فقط بملفات XLSX و XLS و CSV.',
            'file.max' => 'حجم الملف يجب ألا يتجاوز 50 MB.',
        ]);

        $this->analyzing = true;
        $this->preview = null;
        $this->importResult = null;
        $storedFilePath = null;

        try {
            $engine = app(ExcelImportEngine::class);

            $this->preview = $engine->analyze(
                $this->file->getRealPath()
            );
            $storedFilePath = $this->file->store(
    'excel-imports',
    'local'
);

$this->preview['stored_file_path'] = $storedFilePath;

            /*
             * Add employee-editable fields to every row.
             */
            foreach ($this->preview['sheets'] as $sheetIndex => $sheet) {
                foreach ($sheet['rows'] as $rowIndex => $row) {
                    $this->preview['sheets'][$sheetIndex]['rows'][$rowIndex]['name']
                        = $row['name'] ?? '';

                    $this->preview['sheets'][$sheetIndex]['rows'][$rowIndex]['category_id']
                        = $row['category_id'] ?? null;
                }
            }

            Notification::make()
                ->title('تم تحليل الملف')
                ->body('يمكنك الآن مراجعة البيانات وإكمال البيانات المطلوبة.')
                ->success()
                ->send();

        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('تعذر تحليل الملف')
                ->body($exception->getMessage())
                ->danger()
                ->send();

        } finally {
            $this->analyzing = false;
        }
    }

    public function confirmImport(): void
    {
        if (!$this->preview) {
           $created = $this->importResult['created_products'] ?? 0;
$updated = $this->importResult['updated_products'] ?? 0;
$images = $this->importResult['created_images'] ?? 0;
$replacedImages = $this->importResult['updated_images'] ?? 0;
$failed = $this->importResult['failed_rows'] ?? 0;

$body = [];

if ($created > 0) {
    $body[] = "🆕 تم إنشاء {$created} منتج جديد.";
}

if ($updated > 0) {
    $body[] = "🔄 تم تحديث {$updated} منتج موجود مسبقًا.";
}

if ($images > 0) {
    $body[] = "🖼️ تمت إضافة {$images} صورة.";
}

if ($replacedImages > 0) {
    $body[] = "♻️ تمت معالجة {$replacedImages} صورة سابقة من استيراد Excel.";
}

if ($failed > 0) {
    $body[] = "⚠️ الصفوف التي لم تستورد: {$failed}.";
}

if ($created === 0 && $updated > 0) {
    $title = 'تم تحديث البيانات الموجودة';
} elseif ($created > 0 && $updated > 0) {
    $title = 'تم إنشاء وتحديث المنتجات';
} elseif ($created > 0) {
    $title = 'تم إنشاء المنتجات الجديدة';
} else {
    $title = 'تم تنفيذ الاستيراد';
}

Notification::make()
    ->title($title)
    ->body(implode("\n", $body))
    ->success()
    ->send();

            return;
        }

        $this->importing = true;

        try {
            $importer = app(ExcelDatabaseImporter::class);

            $this->importResult = $importer->import(
                $this->preview,
                auth()->id()
            );

            Notification::make()
                ->title('تم تنفيذ الاستيراد')
                ->body(
                    'تم استيراد '
                    . ($this->importResult['successful_rows'] ?? 0)
                    . ' منتج بنجاح.'
                )
                ->success()
                ->send();

        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('فشل الاستيراد')
                ->body($exception->getMessage())
                ->danger()
                ->send();

        } finally {
            $this->importing = false;
        }
    }

    public function resetImport(): void
    {
        $this->reset([
            'file',
            'preview',
            'analyzing',
            'importing',
            'importResult',
        ]);
    }

    public function getSummaryProperty(): array
    {
        return $this->preview['summary'] ?? [
            'sheets' => 0,
            'total_rows' => 0,
            'valid_rows' => 0,
            'warning_rows' => 0,
            'error_rows' => 0,
            'duplicate_rows' => 0,
            'existing_products' => 0,
        ];
    }
}