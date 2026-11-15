<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\ImportBatchItem;
use App\Models\Product;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use App\Services\Imports\ExcelHeaderMapper;

use App\Services\Imports\ExcelRowNormalizer;
class ProductsImport implements
    OnEachRow,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows
{
    use Importable;

   public function __construct(
    protected ImportBatch $batch
) {
    $this->headerMapper = new ExcelHeaderMapper();
    $this->rowNormalizer = new ExcelRowNormalizer();
}

    public function onRow(Row $row): void
    {
        $rowNumber = $row->getIndex();

       $rawRow = $row->toArray();

$normalizedRow = $this->headerMapper->normalizeRow($rawRow);
$data = $this->rowNormalizer->normalize($normalizedRow);

$data = $this->normalizeRow($normalizedRow);

        $importItem = ImportBatchItem::create([
            'import_batch_id' => $this->batch->id,
            'row_number' => $rowNumber,
            'item_no' => $data['item_no'],
            'category_code' => $data['category_code'],
            'status' => 'pending',
            'raw_data' => $rawRow,
        ]);

        try {
            $warnings = [];

            /*
             * ITEM NO is the only mandatory identifier
             * for creating/updating a product from Excel.
             */
            if ($data['item_no'] === null) {
                throw new \RuntimeException(
                    'ITEM NO مفقود أو فارغ.'
                );
            }

            /*
             * Resolve category when CATEGORY_CODE exists.
             */
            $categoryId = null;

            if ($data['category_code'] !== null) {
                $category = Category::query()
                    ->where('category_code', $data['category_code'])
                    ->first();

                if ($category) {
                    $categoryId = $category->id;
                } else {
                    $warnings[] = sprintf(
                        'CATEGORY_CODE "%s" غير موجود في التصنيفات.',
                        $data['category_code']
                    );
                }
            } else {
                $warnings[] = 'CATEGORY_CODE غير موجود أو فارغ.';
            }

            /*
             * Find product by ITEM NO.
             */
            $product = Product::query()
                ->where('item_no', $data['item_no'])
                ->first();

            if ($product) {
                $updates = [];

                /*
                 * Never replace existing useful data with NULL.
                 */
                if ($data['name'] !== null) {
                    $updates['name'] = $data['name'];
                }

                if ($categoryId !== null) {
                    $updates['category_id'] = $categoryId;
                }

                if ($updates !== []) {
                    $product->update($updates);
                }

                $warnings[] = 'المنتج موجود مسبقًا وتم تحديث البيانات المتاحة.';
            } else {
                $product = Product::create([
                    'item_no' => $data['item_no'],
                    'name' => $data['name'],
                    'category_id' => $categoryId,
                    'availability' => 'available',
                    'is_featured' => false,
                    'is_active' => true,
                    'rating' => 0,
                    'rating_count' => 0,
                ]);
            }

            $importItem->update([
                'product_id' => $product->id,
                'status' => empty($warnings)
                    ? 'success'
                    : 'success_with_warning',
                'warnings' => empty($warnings)
                    ? null
                    : $warnings,
            ]);

        } catch (\Throwable $e) {
            $importItem->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Normalize the current row.
     */
    protected function normalizeRow(array $row): array
    {
        return [
            'item_no' => $this->getFirstValue($row, [
                'item_no',
                'item',
                'item_number',
                'itemnumber',
            ]),

            'name' => $this->getFirstValue($row, [
                'name',
                'product_name',
                'product',
                'item_name',
            ]),
'category_code' => $this->cleanCategoryCode(
    $this->getFirstValue($row, [
        'category_code',
        'category',
        'category_no',
        'cat_code',
    ])
),
        ];
    }
    protected ExcelHeaderMapper $headerMapper;
    protected ExcelRowNormalizer $rowNormalizer;

    protected function getFirstValue(
        array $row,
        array $possibleKeys
    ): ?string {
        foreach ($possibleKeys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }

            $value = $this->cleanValue($row[$key]);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    protected function cleanValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $value;
    }

    protected function cleanCategoryCode(mixed $value): ?string
    {
        $value = $this->cleanValue($value);

        if ($value === null) {
            return null;
        }

        return Str::upper($value);
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function batchSize(): int
    {
        return 500;
    }
}