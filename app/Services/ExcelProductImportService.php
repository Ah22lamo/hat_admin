<?php

namespace App\Services;

use App\Models\ImportBatch;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class ExcelProductImportService
{
    public function __construct(
        protected ExcelImageExtractor $imageExtractor
    ) {
    }

    /**
     * Import an Excel file into HAT.
     *
     * The import continues even when individual rows/images fail.
     */
    public function import(
        string $filePath,
        int $userId,
        ?string $originalFileName = null
    ): ImportBatch {
        if (!is_file($filePath)) {
            throw new \RuntimeException(
                "Excel file not found: {$filePath}"
            );
        }

        $spreadsheet = IOFactory::load($filePath);

        $batch = ImportBatch::create([
            'file_name' => $originalFileName ?? basename($filePath),
            'file_path' => $filePath,
            'import_type' => 'products',
            'total_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'status' => 'processing',
            'imported_by' => $userId,
            'started_at' => now(),
        ]);

        $successful = 0;
        $failed = 0;
        $total = 0;

        try {
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $result = $this->importWorksheet(
                    worksheet: $worksheet,
                    batch: $batch
                );

                $total += $result['total'];
                $successful += $result['successful'];
                $failed += $result['failed'];
            }

            $batch->update([
                'total_rows' => $total,
                'successful_rows' => $successful,
                'failed_rows' => $failed,
                'status' => $failed > 0
                    ? 'completed_with_errors'
                    : 'completed',
                'completed_at' => now(),
            ]);

            return $batch->fresh();
        } catch (Throwable $e) {
            $batch->update([
                'total_rows' => $total,
                'successful_rows' => $successful,
                'failed_rows' => $failed,
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            throw $e;
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    /**
     * Import one worksheet.
     */
    protected function importWorksheet(
        Worksheet $worksheet,
        ImportBatch $batch
    ): array {
        $rows = $worksheet->toArray(
            null,
            true,
            true,
            true
        );

        if (empty($rows)) {
            return [
                'total' => 0,
                'successful' => 0,
                'failed' => 0,
            ];
        }

        $headerRowNumber = $this->findHeaderRow($rows);

        if ($headerRowNumber === null) {
            return [
                'total' => 0,
                'successful' => 0,
                'failed' => 0,
            ];
        }

        $headers = $this->normalizeHeaders(
            $rows[$headerRowNumber]
        );

        /*
         * Extract images once for the whole worksheet.
         */
        $images = $this->imageExtractor->extractFromWorksheet(
            worksheet: $worksheet,
            directory: 'products/imports'
        );

        $imagesByRow = $this->indexImagesByRow($images);

        $total = 0;
        $successful = 0;
        $failed = 0;

        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber <= $headerRowNumber) {
                continue;
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $total++;

            $result = $this->importRow(
                row: $row,
                rowNumber: $rowNumber,
                headers: $headers,
                batch: $batch,
                imagesByRow: $imagesByRow
            );

            if ($result['success']) {
                $successful++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
        ];
    }

    /**
     * Import a single Excel row.
     */
    protected function importRow(
        array $row,
        int $rowNumber,
        array $headers,
        ImportBatch $batch,
        array $imagesByRow
    ): array {
        $rawData = $row;

        $data = $this->mapRow(
            row: $row,
            headers: $headers
        );

        $warnings = [];

        try {
            /*
             * ITEM is mandatory.
             */
            $itemCode = trim((string) ($data['item_code'] ?? ''));

            if ($itemCode === '') {
                throw new \RuntimeException(
                    'ITEM / item_code is missing.'
                );
            }

            /*
             * CATEGORY_CODE is mandatory.
             *
             * We deliberately do NOT guess the category.
             */
            $categoryCode = trim(
                (string) ($data['category_code'] ?? '')
            );

            if ($categoryCode === '') {
                throw new \RuntimeException(
                    'CATEGORY_CODE is missing.'
                );
            }

            $categoryId = DB::table('categories')
                ->where('category_code', $categoryCode)
                ->whereNull('deleted_at')
                ->value('id');

            if (!$categoryId) {
                throw new \RuntimeException(
                    "Unknown CATEGORY_CODE: {$categoryCode}"
                );
            }

            /*
             * Product creation/update is deliberately based on item_no.
             */
            $product = Product::withTrashed()
                ->where('item_no', $itemCode)
                ->first();

            if ($product) {
                if ($product->trashed()) {
                    $product->restore();
                }

               $product->update([
    'category_id' => $categoryId,
    'name_ar' => $product->name_ar ?: $itemCode,
]);
            } else {
                $product = Product::create([
    'category_id' => $categoryId,
    'brand_id' => null,
    'item_no' => $itemCode,
    'sku' => null,

    'name_ar' => $itemCode,
    'name_en' => null,

    'description_ar' => null,
    'description_en' => null,

    'base_price' => null,
    'sale_price' => null,
    'currency' => 'CNY',

    'availability' => 'available',

    'is_featured' => false,
    'is_active' => true,

    'rating' => 0,
    'rating_count' => 0,
]);
            }

            /*
             * Save import/logistics data.
             */
            $item = $batch->items()->create([
                'row_number' => $rowNumber,
                'item_no' => $itemCode,
                'item_code' => $itemCode,
                'category_code' => $categoryCode,
                'product_id' => $product->id,

                'status' => 'success',

                'raw_data' => $rawData,

                'cartons_count' => $this->integerOrNull(
                    $data['cartons_count'] ?? null
                ),

                'qty_per_carton' => $this->integerOrNull(
                    $data['qty_per_carton'] ?? null
                ),

                'total_quantity' => $this->integerOrNull(
                    $data['total_quantity'] ?? null
                ),

                'unit_price_fob' => $this->decimalOrNull(
                    $data['unit_price_fob'] ?? null
                ),

                'subtotal_price' => $this->decimalOrNull(
                    $data['subtotal_price'] ?? null
                ),

                'cbm_per_carton' => $this->decimalOrNull(
                    $data['cbm_per_carton'] ?? null
                ),

                'total_cbm' => $this->decimalOrNull(
                    $data['total_cbm'] ?? null
                ),

                'total_weight' => $this->decimalOrNull(
                    $data['total_weight'] ?? null
                ),
            ]);

            /*
             * Image handling NEVER invalidates the product.
             */
            $imageResult = $this->attachImageForRow(
                product: $product,
                rowNumber: $rowNumber,
                imagesByRow: $imagesByRow
            );

            if (!$imageResult['success']) {
                $warnings[] = $imageResult['warning'];
            }

            if (!empty($warnings)) {
                $item->update([
                    'warnings' => $warnings,
                ]);
            }

            return [
                'success' => true,
                'product_id' => $product->id,
                'warnings' => $warnings,
            ];
        } catch (Throwable $e) {
            /*
             * The row fails, but the entire Excel import continues.
             */
            $batch->items()->create([
                'row_number' => $rowNumber,
                'item_no' => $data['item_code'] ?? null,
                'item_code' => $data['item_code'] ?? null,
                'category_code' => $data['category_code'] ?? null,
                'product_id' => null,

                'status' => 'failed',

                'error_message' => $e->getMessage(),

                'raw_data' => $rawData,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Attach the best image candidate to the product.
     */
    protected function attachImageForRow(
        Product $product,
        int $rowNumber,
        array $imagesByRow
    ): array {
        if (!isset($imagesByRow[$rowNumber])) {
            return [
                'success' => false,
                'warning' => "No embedded image found for Excel row {$rowNumber}.",
            ];
        }

        $candidates = $imagesByRow[$rowNumber];

        if (count($candidates) > 1) {
            return [
                'success' => false,
                'warning' => "Multiple images found for Excel row {$rowNumber}; image assignment skipped to avoid incorrect matching.",
            ];
        }

        $image = $candidates[0];

        if (empty($image['success']) || empty($image['path'])) {
            return [
                'success' => false,
                'warning' => "Embedded image could not be extracted for Excel row {$rowNumber}.",
            ];
        }

        try {
            DB::table('product_images')->insert([
                'product_id' => $product->id,
                'path' => $image['path'],
                'disk' => 'public',
                'alt_text' => $product->name,
                'is_primary' => true,
                'sort_order' => 0,
                'source' => 'excel_import',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'success' => true,
                'path' => $image['path'],
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'warning' => "Image database save failed for row {$rowNumber}: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Index extracted images by their Excel anchor row.
     */
    protected function indexImagesByRow(array $images): array
    {
        $indexed = [];

        foreach ($images as $image) {
            if (
                empty($image['success']) ||
                empty($image['row'])
            ) {
                continue;
            }

            $row = (int) $image['row'];

            $indexed[$row][] = $image;
        }

        return $indexed;
    }

    /**
     * Normalize different Excel header names to our canonical names.
     */
    protected function normalizeHeaders(array $headers): array
    {
        $result = [];

        foreach ($headers as $column => $header) {
            $normalized = $this->normalizeHeaderName(
                (string) $header
            );

            if ($normalized !== '') {
                $result[$column] = $normalized;
            }
        }

        return $result;
    }

    protected function normalizeHeaderName(string $header): string
    {
        $header = trim($header);

        $header = mb_strtoupper(
            $header,
            'UTF-8'
        );

        $header = str_replace(
            [' ', '-', '_', '/', '\\', '.', '(', ')'],
            '',
            $header
        );

        return match ($header) {
            'PIC',
            'PICTURE',
            'IMAGE',
            'PRODUCTIMAGE' => 'image',

            'ITEM',
            'ITEMNO',
            'ITEMNUMBER',
            'ITEMCODE',
            'PRODUCTCODE' => 'item_code',

            'CATEGORY',
            'CATEGORYCODE',
            'CAT',
            'CATCODE' => 'category_code',

            'CTNS',
            'CTN',
            'CARTONS',
            'CARTON' => 'cartons_count',

            'PCS/CTN',
            'PCSCTN',
            'PCSINCARTON',
            'QTYCTN' => 'qty_per_carton',

            'QTY',
            'QUANTITY',
            'TOTALQTY',
            'TOTALQUANTITY' => 'total_quantity',

            'PRICE',
            'UNITPRICE',
            'FOBPRICE',
            'UNITPRICEFOB' => 'unit_price_fob',

            'AMOUNT',
            'TOTALPRICE',
            'SUBTOTAL' => 'subtotal_price',

            'CBM',
            'CBMPERCTN',
            'CBMPERCARTON' => 'cbm_per_carton',

            'T/CBM',
            'TCBM',
            'TOTALCBM' => 'total_cbm',

            'T/WEIGHT',
            'TWEIGHT',
            'TOTALWEIGHT',
            'WEIGHT' => 'total_weight',

            default => Str::snake(
                trim($header)
            ),
        };
    }

    /**
     * Convert a normalized row to canonical data.
     */
    protected function mapRow(
        array $row,
        array $headers
    ): array {
        $data = [];

        foreach ($headers as $column => $name) {
            $data[$name] = $row[$column] ?? null;
        }

        return $data;
    }

    protected function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $rowNumber => $row) {
            $normalized = array_map(
                fn ($value) => $this->normalizeHeaderName(
                    (string) $value
                ),
                $row
            );

            if (
                in_array('item_code', $normalized, true)
                &&
                (
                    in_array('cartons_count', $normalized, true)
                    ||
                    in_array('total_quantity', $normalized, true)
                )
            ) {
                return $rowNumber;
            }
        }

        return null;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (
                $value !== null &&
                trim((string) $value) !== ''
            ) {
                return false;
            }
        }

        return true;
    }

    protected function integerOrNull(mixed $value): ?int
    {
        if (
            $value === null ||
            trim((string) $value) === ''
        ) {
            return null;
        }

        $value = str_replace(
            [',', ' '],
            '',
            (string) $value
        );

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    protected function decimalOrNull(mixed $value): ?float
    {
        if (
            $value === null ||
            trim((string) $value) === ''
        ) {
            return null;
        }

        $value = str_replace(
            ['￥', '$', ',', ' '],
            '',
            (string) $value
        );

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}