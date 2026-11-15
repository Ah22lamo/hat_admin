<?php

namespace App\Services\ExcelImport;

use App\Services\ExcelImageExtractor;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class ExcelAnalyzer
{
    public function __construct(
        protected HeaderDetector $headerDetector,
        protected ColumnMapper $columnMapper,
        protected DataNormalizer $normalizer,
        protected DataValidator $validator,
        protected ExcelImageExtractor $imageExtractor,
    ) {}

    /**
     * Analyze an Excel workbook without writing products
     * to the database.
     */
    public function analyze(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException(
                "Excel file was not found: {$filePath}"
            );
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'تعذر قراءة ملف Excel: ' . $exception->getMessage(),
                0,
                $exception
            );
        }

        $sheets = [];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $sheets[] = $this->analyzeSheet($worksheet);
        }

        return [
            'file' => basename($filePath),
            'path' => $filePath,
            'sheet_count' => count($sheets),
            'sheets' => $sheets,
        ];
    }

    /**
     * Analyze one worksheet.
     */
    protected function analyzeSheet(Worksheet $worksheet): array
    {
        $rows = [];

        foreach (
            $worksheet->toArray(
                null,
                true,
                true,
                false
            ) as $row
        ) {
            $rows[] = array_values($row);
        }

        /*
         * Extract embedded Excel images.
         *
         * Important:
         * Image extraction is intentionally isolated from
         * cell analysis. If one image fails, the Excel
         * analysis itself should continue.
         */
        $images = [];

$drawings = $worksheet->getDrawingCollection();

\Log::info('HAT Excel Drawing Diagnostic', [
    'sheet' => $worksheet->getTitle(),
    'drawing_count' => count($drawings),
]);

foreach ($drawings as $drawing) {
    \Log::info('HAT Excel Drawing', [
        'class' => get_class($drawing),
        'coordinate' => method_exists($drawing, 'getCoordinates')
            ? $drawing->getCoordinates()
            : null,
        'path' => method_exists($drawing, 'getPath')
            ? $drawing->getPath()
            : null,
    ]);
}

        try {
            $images = $this->imageExtractor->extractFromWorksheet(
    worksheet: $worksheet,
    directory: 'products/imports'
);

\Log::info('HAT Excel Extracted Images', [
    'sheet' => $worksheet->getTitle(),
    'images' => $images,
]
            );
        } catch (\Throwable $exception) {
            /*
             * Never fail the entire Excel analysis because
             * of image processing.
             */
            $images = [
                [
                    'success' => false,
                    'coordinate' => null,
                    'coordinate2' => null,
                    'row' => null,
                    'column' => null,
                    'path' => null,
                    'filename' => null,
                    'error' => $exception->getMessage(),
                ],
            ];
        }

        /*
         * Group successfully extracted images by Excel row.
         *
         * Example:
         *
         * row 12 => [
         *     image1,
         *     image2
         * ]
         */
        $imagesByRow = [];

        foreach ($images as $image) {
            if (
                !($image['success'] ?? false)
                || !isset($image['row'])
            ) {
                continue;
            }

            $rowNumber = (int) $image['row'];

            $imagesByRow[$rowNumber][] = $image;
        }

        $rowsWithImages = 0;

        foreach ($imagesByRow as $rowNumber => $rowImages) {
            if (!empty($rowImages)) {
                $rowsWithImages++;
            }
        }

        $headerIndex = $this->headerDetector->detect($rows);

        if ($headerIndex === null) {
            return [
                'name' => $worksheet->getTitle(),
                'rows' => count($rows),
                'header_row' => null,
                'columns' => [],
                'unknown_columns' => [],
                'data' => [],
                'images' => $images,
                'images_by_row' => $imagesByRow,
                'summary' => [
                    'total_rows' => 0,
                    'valid' => 0,
                    'warnings' => 0,
                    'errors' => 0,
                    'images' => count($images),
                    'rows_with_images' => $rowsWithImages,
                    'failed_images' => $this->countFailedImages($images),
                ],
                'status' => 'failed',
                'message' => 'لم يتم العثور على صف Header صالح.',
            ];
        }

        $headerRow = $rows[$headerIndex];

        $mapping = $this->columnMapper->map($headerRow);

        $unknownColumns = $this->columnMapper->unknownColumns(
            $headerRow
        );

        $dataRows = array_slice(
            $rows,
            $headerIndex + 1
        );

        $normalizedRows = [];

        $summary = [
            'total_rows' => 0,
            'valid' => 0,
            'warnings' => 0,
            'errors' => 0,
            'images' => count($images),
            'rows_with_images' => $rowsWithImages,
            'failed_images' => $this->countFailedImages($images),
        ];

        foreach ($dataRows as $index => $rawRow) {
            if ($this->isEmptyRow($rawRow)) {
                continue;
            }

            if ($this->isFooterOrSummaryRow($rawRow)) {
                continue;
            }

            $rowNumber = $headerIndex + $index + 2;

            $normalized = $this->buildNormalizedRow(
                $rawRow,
                $mapping
            );

            /*
             * Ignore rows that contain no meaningful mapped data.
             */
            if (!$this->hasMeaningfulData($normalized)) {
                continue;
            }

            $normalized['_row_number'] = $rowNumber;

            /*
             * Preserve original Excel row for audit/debugging.
             */
            $normalized['_raw_data'] = $rawRow;

            /*
             * Attach embedded images belonging to this Excel row.
             *
             * image_path:
             *     First image, for backward compatibility.
             *
             * image_paths:
             *     All images belonging to this row.
             *
             * images:
             *     Full extracted image metadata.
             */
            /*
 * Attach embedded Excel images to this product row.
 *
 * Excel drawing coordinates are 1-based, e.g. A2, A3, A4.
 * $rowNumber is also the real Excel row number.
 */
$rowImages = $imagesByRow[$rowNumber] ?? [];

$normalized['image_path'] = null;
$normalized['image_paths'] = [];
$normalized['images'] = [];

foreach ($rowImages as $image) {

    if (
        !($image['success'] ?? false) ||
        blank($image['path'] ?? null)
    ) {
        continue;
    }

    $path = $image['path'];

    $normalized['images'][] = $image;

    $normalized['image_paths'][] = $path;

    if ($normalized['image_path'] === null) {
        $normalized['image_path'] = $path;
    }
}

/*
 * Diagnostic log:
 * This confirms whether the extracted image reached
 * the normalized Preview row.
 */
\Log::info('HAT Excel Row Image Mapping', [
    'sheet' => $worksheet->getTitle(),
    'excel_row' => $rowNumber,
    'item_no' => $normalized['item_no'] ?? null,
    'image_count' => count($normalized['image_paths']),
    'image_path' => $normalized['image_path'],
    'image_paths' => $normalized['image_paths'],
]);

            /*
             * Image extraction failures become warnings,
             * never row-fatal errors.
             */
            foreach ($images as $image) {
                if (
                    ($image['success'] ?? false) === false
                    && ($image['row'] ?? null) === $rowNumber
                ) {
                    $normalized['_image_warnings'][] =
                        $image['error'] ?? 'تعذر استخراج صورة Excel.';
                }
            }

            $validation = $this->validator->validate(
                $normalized
            );

            $normalized['_status'] = $validation['status'];
            $normalized['_errors'] = $validation['errors'];
            $normalized['_warnings'] = $validation['warnings'];

            /*
             * Add image warnings after normal validation.
             */
            foreach (
                $normalized['_image_warnings'] ?? []
                as $imageWarning
            ) {
                $normalized['_warnings'][] =
                    'صورة المنتج: ' . $imageWarning;
            }

            if (
                $normalized['_status'] === 'success'
                && !empty($normalized['_warnings'])
            ) {
                $normalized['_status'] = 'success_with_warning';
            }

            $normalizedRows[] = $normalized;

            $summary['total_rows']++;

            match ($normalized['_status']) {
                'success' => $summary['valid']++,
                'success_with_warning' => $summary['warnings']++,
                'failed' => $summary['errors']++,
                default => null,
            };
        }

        return [
            'name' => $worksheet->getTitle(),
            'rows' => count($rows),
            'header_row' => $headerIndex + 1,
            'columns' => $mapping,
            'unknown_columns' => $unknownColumns,
            'data' => $normalizedRows,

            /*
             * Image information at worksheet level.
             */
            'images' => $images,
            'images_by_row' => $imagesByRow,

            'summary' => $summary,
            'status' => 'success',
        ];
    }

    /**
     * Normalize a raw Excel row.
     */
    protected function buildNormalizedRow(
        array $row,
        array $mapping
    ): array {
        $result = [];

        foreach ($mapping as $column => $map) {
            $field = $map['field'] ?? null;

            if ($field === null) {
                continue;
            }

            $value = $row[$column] ?? null;

            $value = $this->normalizer->normalizeValue($value);

            if (
                in_array(
                    $field,
                    [
                        'cartons_count',
                        'qty_per_carton',
                        'total_quantity',
                        'unit_price',
                        'subtotal_price',
                        'cbm_per_carton',
                        'total_cbm',
                    ],
                    true
                )
            ) {
                $value = $this->normalizer->normalizeNumber($value);
            }

            if ($field === 'item_no') {
                $value = $this->normalizer->normalizeItemNumber(
                    $value
                );
            }

            $result[$field] = $value;
        }

        return $result;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (
                $value !== null
                && trim((string) $value) !== ''
            ) {
                return false;
            }
        }

        return true;
    }

    protected function isFooterOrSummaryRow(array $row): bool
    {
        $text = '';

        foreach ($row as $value) {
            if ($value === null) {
                continue;
            }

            $text .= ' ' . mb_strtolower(
                trim((string) $value)
            );
        }

        $text = trim($text);

        if ($text === '') {
            return false;
        }

        $summaryWords = [
            'grand total',
            'total',
            'subtotal',
            'summary',
            '合计',
            '总计',
            '小计',
            'الإجمالي',
            'المجموع',
            'المجموع الكلي',
        ];

        foreach ($summaryWords as $word) {
            if ($text === $word) {
                return true;
            }

            if (
                str_starts_with(
                    $text,
                    $word . ' '
                )
            ) {
                return true;
            }
        }

        return false;
    }

    protected function hasMeaningfulData(array $row): bool
    {
        foreach ($row as $key => $value) {
            if (str_starts_with((string) $key, '_')) {
                continue;
            }

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Count image extraction failures.
     */
    protected function countFailedImages(array $images): int
    {
        return count(
            array_filter(
                $images,
                fn (array $image): bool =>
                    ($image['success'] ?? false) === false
            )
        );
    }
}

