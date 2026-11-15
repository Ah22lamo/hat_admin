<?php

namespace App\Services\ExcelImport;

class HeaderDetector
{
    /**
     * Known column aliases used by HAT Excel files.
     */
protected array $aliases = [
    'item_no' => [
        'item',
        'item no',
        'item number',
        'item code',
        'code',
        'product code',
    ],

    'cartons_count' => [
        'ctns',
        'ctn',
        'cartons',
        'carton',
        'carton qty',
        'cartons qty',
    ],

    'qty_per_carton' => [
        'pcs/ctn',
        'pcs / ctn',
        'pcs per ctn',
        'pcs/ carton',
        'pcs/carton',
        'qty/ctn',
        'qty / ctn',
        'quantity per carton',
    ],

    'total_quantity' => [
        'qty',
        'quantity',
        'total qty',
        'total quantity',
        'pcs',
        'total pcs',
    ],

    'unit_price' => [
        'price',
        'unit price',
        'unit cost',
        'cost',
        'fob',
        'fob price',
    ],

    'subtotal_price' => [
        'amount',
        'total',
        'total amount',
        'subtotal',
        'subtotal amount',
    ],

    'cbm_per_carton' => [
        'cbm',
        'cbm/ctn',
        'cbm / ctn',
        'carton cbm',
    ],

    'total_cbm' => [
        't/cbm',
        't / cbm',
        'total cbm',
    ],

    'pic' => [
        'pic',
        'picture',
        'photo',
        'image',
        'images',
    ],
];


    /**
     * Detect the most likely header row.
     *
     * @param array<int, array<int|string, mixed>> $rows
     */
    public function detect(array $rows): ?int
    {
        $bestRow = null;
        $bestScore = 0;

        foreach ($rows as $rowIndex => $row) {
            $score = $this->scoreRow($row);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow = $rowIndex;
            }
        }

        return $bestScore > 0 ? $bestRow : null;
    }

    /**
     * Calculate how strongly a row looks like a header.
     *
     * @param array<int|string, mixed> $row
     */
    public function scoreRow(array $row): int
    {
        $score = 0;
        $matchedFields = [];

        foreach ($row as $value) {
            $normalized = $this->normalizeHeader($value);

            if ($normalized === null) {
                continue;
            }

            foreach ($this->aliases as $field => $aliases) {
                if (in_array($normalized, $aliases, true)) {
                    if (!in_array($field, $matchedFields, true)) {
                        $matchedFields[] = $field;
                        $score++;
                    }

                    break;
                }
            }
        }

        return $score;
    }

    /**
     * Return the normalized mapping for a detected header row.
     *
     * @param array<int|string, mixed> $headerRow
     * @return array<int|string, string>
     */
    public function mapColumns(array $headerRow): array
    {
        $mapping = [];

        foreach ($headerRow as $column => $value) {
            $normalized = $this->normalizeHeader($value);

            if ($normalized === null) {
                continue;
            }

            $field = $this->findField($normalized);

            if ($field !== null) {
                $mapping[$column] = $field;
            }
        }

        return $mapping;
    }

    protected function findField(string $header): ?string
    {
        foreach ($this->aliases as $field => $aliases) {
            if (in_array($header, $aliases, true)) {
                return $field;
            }
        }

        return null;
    }

    protected function normalizeHeader(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = mb_strtolower($value);

        $value = str_replace(
            ['_', '-', '–', '—'],
            ' ',
            $value
        );

        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }
}