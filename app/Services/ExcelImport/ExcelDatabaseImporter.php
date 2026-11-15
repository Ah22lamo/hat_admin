<?php

namespace App\Services\ExcelImport;

use App\Models\ImportBatch;
use App\Models\ImportBatchItem;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExcelDatabaseImporter
{
    public function import(array $preview, ?int $userId = null): array
    {
        return DB::transaction(function () use ($preview, $userId) {
            $fileName = $preview['file']['name']
                ?? basename($preview['file']['path'] ?? 'excel-import');

            $storedFilePath = $preview['stored_file_path'] ?? null;

            $batch = ImportBatch::create([
                'file_name' => $fileName,
                'file_path' => $storedFilePath,
                'import_type' => 'products',
                'total_rows' => $preview['summary']['total_rows'] ?? 0,
                'successful_rows' => 0,
                'failed_rows' => 0,
                'status' => 'processing',
                'imported_by' => $userId,
                'started_at' => now(),
            ]);

            $successfulRows = 0;
            $failedRows = 0;
            $createdProducts = 0;
            $updatedProducts = 0;
            $createdImages = 0;
            $updatedImages = 0;

            $processedItems = [];

            try {
                foreach ($preview['sheets'] ?? [] as $sheet) {
                    foreach ($sheet['rows'] ?? [] as $row) {
                        $rowNumber = $row['_row_number'] ?? null;
                        $itemNo = $this->normalizeItem($row['item_no'] ?? null);

                        /*
                         * Skip rows that failed validation during analysis.
                         */
                        if (($row['_status'] ?? 'failed') === 'failed') {
                            $failedRows++;

                            $this->saveBatchItem(
                                $batch,
                                $row,
                                'failed',
                                implode(
                                    ' ',
                                    $row['_errors'] ?? ['فشل التحقق من بيانات الصف.']
                                )
                            );

                            continue;
                        }

                        /*
                         * ITEM is mandatory for a product.
                         */
                        if (!$itemNo) {
                            $failedRows++;

                            $this->saveBatchItem(
                                $batch,
                                $row,
                                'failed',
                                'رقم الصنف ITEM مطلوب.'
                            );

                            continue;
                        }

                        /*
                         * Prevent importing the same ITEM twice from
                         * the same Excel file.
                         */
                        if (isset($processedItems[$itemNo])) {
                            $this->saveBatchItem(
                                $batch,
                                $row,
                                'skipped',
                                'تم تجاهل الصف لأن ITEM مكرر داخل ملف Excel.'
                            );

                            continue;
                        }

                        $processedItems[$itemNo] = true;

                        /*
                         * Find existing product by ITEM number.
                         */
                        $product = Product::query()
                            ->where('item_no', $itemNo)
                            ->first();

                        $operation = $product ? 'updated' : 'created';

                        /*
                         * Resolve category.
                         */
                        $categoryId = $this->resolveCategoryId($row);

                        /*
                         * Product name:
                         * keep existing name if the Excel row does not
                         * provide one.
                         */
                        $productName = trim((string) ($row['name'] ?? ''));

                        if ($productName === '') {
                            $productName = $product?->name ?? $itemNo;
                        }

                        $productData = [
                            'category_id' => $categoryId ?: $product?->category_id,
                            'item_no' => $itemNo,
                            'name' => $productName,
                            'sku' => $this->nullableString($row['sku'] ?? null),
                            'barcode' => $this->nullableString($row['barcode'] ?? null),
                            'description' => $this->nullableString($row['description'] ?? null),
                            'availability' => $row['availability'] ?? 'available',
                            'is_active' => array_key_exists('is_active', $row)
                                ? (bool) $row['is_active']
                                : ($product?->is_active ?? true),
                        ];

                        /*
                         * Do not overwrite existing optional values with null
                         * when the Excel file does not contain those columns.
                         */
                        if ($product) {
                            foreach ([
                                'category_id',
                                'sku',
                                'barcode',
                                'description',
                            ] as $field) {
                                if ($productData[$field] === null) {
                                    $productData[$field] = $product->{$field};
                                }
                            }

                            $product->update($productData);
                            $updatedProducts++;
                        } else {
                            $product = Product::create($productData);
                            $createdProducts++;
                        }

                        /*
                         * Save import row details.
                         */
                        $this->saveBatchItem(
                            $batch,
                            $row,
                            'success',
                            null,
                            $product->id
                        );

                        /*
                         * Import Excel embedded images.
                         *
                         * Existing Excel images are replaced only when
                         * the new Excel row actually contains images.
                         *
                         * Manually uploaded/non-Excel images are preserved.
                         */
                        $imagePaths = $this->extractImagePaths($row);

                        if (!empty($imagePaths)) {
                            $imageResult = $this->importProductImages(
                                $product,
                                $imagePaths,
                                true
                            );

                            $createdImages += $imageResult['created'];
                            $updatedImages += $imageResult['replaced'];
                        }

                        $successfulRows++;
                    }
                }

                $batch->update([
                    'successful_rows' => $successfulRows,
                    'failed_rows' => $failedRows,
                    'status' => $failedRows > 0
                        ? 'completed_with_errors'
                        : 'completed',
                    'completed_at' => now(),
                ]);

                return [
                    'status' => $failedRows > 0
                        ? 'completed_with_errors'
                        : 'success',

                    'batch_id' => $batch->id,

                    'total_rows' => $preview['summary']['total_rows'] ?? 0,
                    'successful_rows' => $successfulRows,
                    'failed_rows' => $failedRows,

                    'created_products' => $createdProducts,
                    'updated_products' => $updatedProducts,

                    'created_images' => $createdImages,
                    'updated_images' => $updatedImages,

                    'message' => sprintf(
                        'تم إنشاء %d منتج وتحديث %d منتج وإضافة %d صورة.',
                        $createdProducts,
                        $updatedProducts,
                        $createdImages
                    ),
                ];
            } catch (\Throwable $exception) {
                $batch->update([
                    'successful_rows' => $successfulRows,
                    'failed_rows' => $failedRows,
                    'status' => 'failed',
                    'completed_at' => now(),
                ]);

                throw $exception;
            }
        });
    }

    protected function resolveCategoryId(array $row): ?int
    {
        if (!empty($row['category_id'])) {
            return (int) $row['category_id'];
        }

        if (!empty($row['category_code'])) {
            $category = \App\Models\Category::query()
                ->where('category_code', trim((string) $row['category_code']))
                ->first();

            if ($category) {
                return $category->id;
            }
        }

        if (!empty($row['category'])) {
            $category = \App\Models\Category::query()
                ->where('name', trim((string) $row['category']))
                ->first();

            if ($category) {
                return $category->id;
            }
        }

        return null;
    }

    protected function extractImagePaths(array $row): array
    {
        $paths = [];

        if (!empty($row['image_paths']) && is_array($row['image_paths'])) {
            $paths = array_merge($paths, $row['image_paths']);
        }

        if (!empty($row['image_path'])) {
            $paths[] = $row['image_path'];
        }

        if (!empty($row['images']) && is_array($row['images'])) {
            foreach ($row['images'] as $image) {
                if (is_array($image) && !empty($image['path'])) {
                    $paths[] = $image['path'];
                }
            }
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        fn ($path) => is_string($path) ? trim($path) : null,
                        $paths
                    )
                )
            )
        );
    }

    protected function importProductImages(
        Product $product,
        array $imagePaths,
        bool $replaceExisting = false
    ): array {
        $created = 0;
        $replaced = 0;

        if ($replaceExisting) {
            /*
             * Remove only images imported from Excel.
             * Manually uploaded images remain untouched.
             */
            $oldExcelImages = ProductImage::query()
                ->where('product_id', $product->id)
                ->where('source', 'excel')
                ->get();

            foreach ($oldExcelImages as $oldImage) {
                if ($oldImage->disk && $oldImage->path) {
                    try {
                        Storage::disk($oldImage->disk)->delete($oldImage->path);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }

                $oldImage->delete();
                $replaced++;
            }
        }

        /*
         * Make sure only the first newly imported image is primary.
         */
        $hasPrimary = ProductImage::query()
            ->where('product_id', $product->id)
            ->where('is_primary', true)
            ->exists();

        foreach ($imagePaths as $index => $imagePath) {
            if (!Storage::disk('public')->exists($imagePath)) {
                continue;
            }

            /*
             * If Excel is replacing the Excel image set, the first
             * imported image becomes primary unless another manual
             * primary image already exists.
             */
            $isPrimary = !$hasPrimary && $index === 0;

            ProductImage::create([
                'product_id' => $product->id,
                'path' => $imagePath,
                'disk' => 'public',
                'alt_text' => $product->name,
                'is_primary' => $isPrimary,
                'sort_order' => $index,
                'source' => 'excel',
            ]);

            if ($isPrimary) {
                $hasPrimary = true;
            }

            $created++;
        }

        return [
            'created' => $created,
            'replaced' => $replaced,
        ];
    }

    protected function saveBatchItem(
        ImportBatch $batch,
        array $row,
        string $status,
        ?string $errorMessage = null,
        ?int $productId = null
    ): ImportBatchItem {
        return ImportBatchItem::create([
            'import_batch_id' => $batch->id,
            'row_number' => $row['_row_number'] ?? null,
            'item_no' => $this->normalizeItem($row['item_no'] ?? null),
            'item_code' => $this->nullableString($row['item_code'] ?? null),
            'category_code' => $this->nullableString($row['category_code'] ?? null),
            'product_id' => $productId,
            'status' => $status,
            'error_message' => $errorMessage,
            'warnings' => !empty($row['_warnings'])
                ? json_encode($row['_warnings'], JSON_UNESCAPED_UNICODE)
                : null,
            'raw_data' => json_encode($row, JSON_UNESCAPED_UNICODE),
            'cartons_count' => $this->nullableNumber($row['cartons_count'] ?? null),
            'qty_per_carton' => $this->nullableNumber($row['qty_per_carton'] ?? null),
            'total_quantity' => $this->nullableNumber($row['total_quantity'] ?? null),
            'unit_price_fob' => $this->nullableNumber($row['unit_price_fob'] ?? null),
            'subtotal_price' => $this->nullableNumber($row['subtotal_price'] ?? null),
            'cbm_per_carton' => $this->nullableNumber($row['cbm_per_carton'] ?? null),
            'total_cbm' => $this->nullableNumber($row['total_cbm'] ?? null),
            'total_weight' => $this->nullableNumber($row['total_weight'] ?? null),
            'landed_unit_cost_350' => $this->nullableNumber($row['landed_unit_cost_350'] ?? null),
            'landed_carton_cost_350' => $this->nullableNumber($row['landed_carton_cost_350'] ?? null),
            'landed_unit_cost_300' => $this->nullableNumber($row['landed_unit_cost_300'] ?? null),
            'landed_carton_cost_300' => $this->nullableNumber($row['landed_carton_cost_300'] ?? null),
            'image_path' => $row['image_path'] ?? null,
            'shipping_rate' => $this->nullableNumber($row['shipping_rate'] ?? null),
        ]);
    }

    protected function normalizeItem($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function nullableNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace([',', '￥', '¥', '$'], '', trim($value));
        }

        return is_numeric($value) ? (float) $value : null;
    }
}