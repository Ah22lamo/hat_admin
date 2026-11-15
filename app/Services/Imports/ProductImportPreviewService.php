<?php

namespace App\Services\Imports;


use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductImportPreviewService
{
public function analyze(string $path): array    {
       $temporaryPath = $path;

        $spreadsheet = IOFactory::load($temporaryPath);

        $result = [
            'file_name' => basename($path),
            'sheets' => [],
            'total_rows' => 0,
            'warnings' => [],
            'errors' => [],
        ];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $sheetResult = $this->analyzeSheet($worksheet);

            $result['sheets'][] = $sheetResult;
            $result['total_rows'] += $sheetResult['data_rows'];

            foreach ($sheetResult['warnings'] as $warning) {
                $result['warnings'][] = $warning;
            }

            foreach ($sheetResult['errors'] as $error) {
                $result['errors'][] = $error;
            }
        }

        return $result;
    }

    protected function analyzeSheet(Worksheet $worksheet): array
    {
        $highestRow = $worksheet->getHighestDataRow();
        $highestColumn = $worksheet->getHighestDataColumn();

        $headers = $worksheet
            ->rangeToArray(
                "A1:{$highestColumn}1",
                null,
                true,
                true,
                true
            )[1] ?? [];

        $normalizedHeaders = [];

        foreach ($headers as $column => $header) {
            $normalizedHeaders[$column] = $this->normalizeHeader(
                (string) $header
            );
        }

        $result = [
            'name' => $worksheet->getTitle(),
            'data_rows' => max(0, $highestRow - 1),
            'headers' => $normalizedHeaders,
            'has_item_no' => in_array(
                'item_no',
                $normalizedHeaders,
                true
            ),
            'has_category_code' => in_array(
                'category_code',
                $normalizedHeaders,
                true
            ),
            'has_pic' => in_array(
                'pic',
                $normalizedHeaders,
                true
            ),
            'warnings' => [],
            'errors' => [],
        ];

        if (!$result['has_item_no']) {
            $result['errors'][] =
                'لم يتم العثور على عمود ITEM NO في ورقة '
                . $worksheet->getTitle();
        }

        if (!$result['has_category_code']) {
            $result['warnings'][] =
                'CATEGORY_CODE غير موجود في ورقة '
                . $worksheet->getTitle()
                . '. سيتم استيراد المنتجات بدون تصنيف تلقائي.';
        }

        if (!$result['has_pic']) {
            $result['warnings'][] =
                'عمود PIC غير موجود في ورقة '
                . $worksheet->getTitle()
                . '.';
        }

        return $result;
    }

    protected function normalizeHeader(string $header): string
    {
        $header = trim($header);
        $header = preg_replace('/\s+/', ' ', $header);

        $normalized = mb_strtolower($header);

        return match ($normalized) {
            'item no',
            'item_no',
            'item number',
            'item_number',
            'itemno' => 'item_no',

            'category code',
            'category_code',
            'category no',
            'category_no' => 'category_code',

            'pic',
            'picture',
            'image' => 'pic',

            'ctns',
            'ctn',
            'cartons' => 'ctns',

            'qty/ctn',
            'qty per ctn',
            'qty_per_ctn' => 'qty_per_ctn',

            't.qty',
            't qty',
            'total qty',
            'total_qty' => 'total_qty',

            'u.price',
            'u price',
            'unit price',
            'unit_price' => 'unit_price',

            'sub total',
            'subtotal',
            'sub_total' => 'subtotal',

            'cbm' => 'cbm',

            't.cbm',
            't cbm',
            'total cbm',
            'total_cbm' => 'total_cbm',

            't.weght',
            't weight',
            't.weight',
            'total weight',
            'total_weight' => 'total_weight',

            default => preg_replace(
                '/[^a-z0-9]+/',
                '_',
                $normalized
            ),
        };
    }
}