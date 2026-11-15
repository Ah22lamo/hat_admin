<?php

namespace App\Services\ExcelImport;

class ImportResult
{
    public function __construct(
        protected DuplicateDetector $duplicateDetector,
    ) {}

    public function build(array $analysis): array
    {
        $result = [
            'file' => $analysis['file'] ?? null,

            'status' => 'success',

            'summary' => [
                'sheets' => 0,
                'total_rows' => 0,
                'valid_rows' => 0,
                'warning_rows' => 0,
                'error_rows' => 0,
                'duplicate_rows' => 0,
                'existing_products' => 0,
            ],

            'sheets' => [],

            'duplicates' => [
                'within_file' => [],
                'existing_products' => [],
            ],

            'errors' => [],
            'warnings' => [],
        ];

        foreach ($analysis['sheets'] ?? [] as $sheet) {
            $sheetResult = $this->processSheet($sheet);

            $result['sheets'][] = $sheetResult;

            $result['summary']['sheets']++;

            $result['summary']['total_rows']
                += $sheetResult['summary']['total_rows'];

            $result['summary']['valid_rows']
                += $sheetResult['summary']['valid_rows'];

            $result['summary']['warning_rows']
                += $sheetResult['summary']['warning_rows'];

            $result['summary']['error_rows']
                += $sheetResult['summary']['error_rows'];

            $result['summary']['duplicate_rows']
                += $sheetResult['summary']['duplicate_rows'];

            $result['summary']['existing_products']
                += $sheetResult['summary']['existing_products'];

            foreach ($sheetResult['errors'] as $error) {
                $result['errors'][] = $error;
            }

            foreach ($sheetResult['warnings'] as $warning) {
                $result['warnings'][] = $warning;
            }

            $result['duplicates']['within_file'] = array_merge(
                $result['duplicates']['within_file'],
                $sheetResult['duplicates']['within_file']
            );

            $result['duplicates']['existing_products'] = array_merge(
                $result['duplicates']['existing_products'],
                $sheetResult['duplicates']['existing_products']
            );
        }

        $result['status'] = $this->determineOverallStatus($result);

        return $result;
    }

    protected function processSheet(array $sheet): array
    {
        $rows = $sheet['data'] ?? [];

        $sheetResult = [
            'name' => $sheet['name'] ?? 'Sheet',
            'header_row' => $sheet['header_row'] ?? null,

            'summary' => [
                'total_rows' => 0,
                'valid_rows' => 0,
                'warning_rows' => 0,
                'error_rows' => 0,
                'duplicate_rows' => 0,
                'existing_products' => 0,
            ],

            'duplicates' => [
                'within_file' => [],
                'existing_products' => [],
            ],

            'errors' => [],
            'warnings' => [],

            'rows' => $rows,
        ];

        if (($sheet['status'] ?? null) === 'failed') {
            $sheetResult['errors'][] = [
                'sheet' => $sheetResult['name'],
                'message' => $sheet['message'] ?? 'فشل تحليل Sheet.',
            ];

            return $sheetResult;
        }

        $sheetResult['summary']['total_rows'] = count($rows);

        $duplicatesWithinFile =
            $this->duplicateDetector->detectWithinFile($rows);

        $existingProducts =
            $this->duplicateDetector->detectExisting($rows);

        $sheetResult['duplicates']['within_file'] =
            $duplicatesWithinFile;

        $sheetResult['duplicates']['existing_products'] =
            $existingProducts;

        $duplicateIndexes = [];

        foreach ($duplicatesWithinFile as $indexes) {
            foreach ($indexes as $index) {
                $duplicateIndexes[$index] = true;
            }
        }

        $sheetResult['summary']['duplicate_rows'] =
            count($duplicateIndexes);

        $sheetResult['summary']['existing_products'] =
            count($existingProducts);

        foreach ($rows as $index => $row) {
            $status = $row['_status'] ?? 'failed';

            if ($status === 'success') {
                $sheetResult['summary']['valid_rows']++;
            }

            if ($status === 'success_with_warning') {
                $sheetResult['summary']['warning_rows']++;
            }

            if ($status === 'failed') {
                $sheetResult['summary']['error_rows']++;
            }

            if (!empty($row['_errors'])) {
                foreach ($row['_errors'] as $error) {
                    $sheetResult['errors'][] = [
                        'row' => $row['_row_number'] ?? ($index + 1),
                        'item_no' => $row['item_no'] ?? null,
                        'message' => $error,
                    ];
                }
            }

            if (!empty($row['_warnings'])) {
                foreach ($row['_warnings'] as $warning) {
                    $sheetResult['warnings'][] = [
                        'row' => $row['_row_number'] ?? ($index + 1),
                        'item_no' => $row['item_no'] ?? null,
                        'message' => $warning,
                    ];
                }
            }
        }

        foreach ($duplicateIndexes as $index => $_) {
            if (!isset($rows[$index])) {
                continue;
            }

            $sheetResult['warnings'][] = [
                'row' => $rows[$index]['_row_number'] ?? ($index + 1),
                'item_no' => $rows[$index]['item_no'] ?? null,
                'message' => 'رقم الصنف مكرر داخل ملف Excel.',
            ];
        }

        foreach ($existingProducts as $itemNo => $productId) {
            $sheetResult['warnings'][] = [
                'row' => null,
                'item_no' => $itemNo,
'message' => "المنتج موجود مسبقًا وسيتم تحديث بياناته عند تأكيد الاستيراد. ID: {$productId}.",            ];
        }

        return $sheetResult;
    }

    protected function determineOverallStatus(array $result): string
    {
        if ($result['summary']['error_rows'] > 0) {
            return 'failed';
        }

        if (
            $result['summary']['warning_rows'] > 0
            || $result['summary']['duplicate_rows'] > 0
            || $result['summary']['existing_products'] > 0
        ) {
            return 'completed_with_warnings';
        }

        return 'success';
    }
}