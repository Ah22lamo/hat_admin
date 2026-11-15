<?php

namespace App\Services\ExcelImport;

class ColumnMapper
{
    /**
     * Canonical fields used by the import engine.
     *
     * Every Excel header alias is normalized before matching.
     */
    protected array $fields = [

        /*
         * ---------------------------------------------------------
         * Product Name
         * ---------------------------------------------------------
         */
        'name' => [
            'name',
            'product name',
            'product_name',
            'product title',
            'item name',
            'item_name',
            'item title',
            'item description',
            'product description',
            'product',
            'name ar',
            'name en',
            'name (ar)',
            'name (en)',
            'product name ar',
            'product name en',
            'product name (ar)',
            'product name (en)',
            'product name arabic',
            'product name english',
            'product name (arabic)',
            'product name (english)',

            // Arabic aliases
            'اسم',
            'اسم المنتج',
            'اسم الصنف',
            'اسم المنتج بالعربي',
            'اسم المنتج العربي',
            'اسم الصنف بالعربي',
            'اسم الصنف العربي',
            'وصف المنتج',
            'وصف الصنف',
        ],


        /*
         * ---------------------------------------------------------
         * Item Number / Item Code
         * ---------------------------------------------------------
         */
        'item_no' => [
            'item',
            'item no',
            'item no.',
            'item number',
            'item code',
            'item_code',
            'code',
            'product code',
            'product_code',
            'product no',
            'product no.',
            'product number',
            'part no',
            'part no.',
            'part number',
            'item id',
            'product id',
        ],


        /*
         * ---------------------------------------------------------
         * Cartons
         * ---------------------------------------------------------
         */
        'cartons_count' => [
            'ctns',
            'ctn',
            'cartons',
            'carton',
            'cartons qty',
            'carton qty',
            'cartons quantity',
            'carton quantity',
            'number of cartons',
            'no of cartons',
        ],


        /*
         * ---------------------------------------------------------
         * Quantity Per Carton
         * ---------------------------------------------------------
         */
        'qty_per_carton' => [
            'pcs/ctn',
            'pcs / ctn',
            'pcs per ctn',
            'pcs/carton',
            'pcs / carton',
            'pcs per carton',
            'qty/ctn',
            'qty / ctn',
            'qty per ctn',
            'qty per carton',
            'quantity per carton',
            'pieces per carton',
            'pieces per ctn',
        ],


        /*
         * ---------------------------------------------------------
         * Total Quantity
         * ---------------------------------------------------------
         */
        'total_quantity' => [
            'qty',
            'quantity',
            'total qty',
            'total quantity',
            'pcs',
            'total pcs',
            'total pieces',
            'quantity total',
        ],


        /*
         * ---------------------------------------------------------
         * Unit Price
         * ---------------------------------------------------------
         */
        'unit_price' => [
            'price',
            'unit price',
            'unit_price',
            'unit cost',
            'cost',
            'fob',
            'fob price',
            'fob unit price',
            'unit fob',
            'unit fob price',
        ],


        /*
         * ---------------------------------------------------------
         * Subtotal / Amount
         * ---------------------------------------------------------
         */
        'subtotal_price' => [
            'amount',
            'total amount',
            'subtotal',
            'subtotal amount',
            'total price',
            'total cost',
            'line total',
            'line amount',
            'extended amount',
        ],


        /*
         * ---------------------------------------------------------
         * CBM Per Carton
         * ---------------------------------------------------------
         */
        'cbm_per_carton' => [
            'cbm',
            'cbm/ctn',
            'cbm / ctn',
            'carton cbm',
            'cbm per carton',
            'carton volume',
        ],


        /*
         * ---------------------------------------------------------
         * Total CBM
         * ---------------------------------------------------------
         */
        'total_cbm' => [
            't/cbm',
            't / cbm',
            'total cbm',
            'total_cbm',
            'total volume',
            'volume total',
        ],


        /*
         * ---------------------------------------------------------
         * Picture / Image
         * ---------------------------------------------------------
         */
        'pic' => [
            'pic',
            'picture',
            'photo',
            'image',
            'images',
            'product image',
            'product images',
            'product photo',
            'product picture',
        ],
    ];


    /**
     * Map an Excel header row to canonical import fields.
     *
     * @param array<int|string, mixed> $headers
     * @return array<int|string, array{
     *     field: string|null,
     *     confidence: float,
     *     header: string
     * }>
     */
    public function map(array $headers): array
    {
        $mapping = [];
        $usedFields = [];

        foreach ($headers as $column => $header) {

            $normalizedHeader = $this->normalize($header);

            if ($normalizedHeader === null) {

                $mapping[$column] = [
                    'field' => null,
                    'confidence' => 0,
                    'header' => '',
                ];

                continue;
            }


            $match = $this->findBestMatch(
                $normalizedHeader,
                $usedFields
            );


            if ($match === null) {

                $mapping[$column] = [
                    'field' => null,
                    'confidence' => 0,
                    'header' => $normalizedHeader,
                ];

                continue;
            }


            $usedFields[] = $match['field'];


            $mapping[$column] = [
                'field' => $match['field'],
                'confidence' => $match['confidence'],
                'header' => $normalizedHeader,
            ];
        }

        return $mapping;
    }


    /**
     * Return only successfully mapped columns.
     */
    public function mappedColumns(array $headers): array
    {
        $mapping = $this->map($headers);

        return array_filter(
            $mapping,
            fn (array $item): bool =>
                $item['field'] !== null
        );
    }


    /**
     * Return columns that the engine does not recognize.
     */
    public function unknownColumns(array $headers): array
    {
        $mapping = $this->map($headers);

        $unknown = [];

        foreach ($mapping as $column => $item) {

            if (
                $item['field'] === null
                && $item['header'] !== ''
            ) {
                $unknown[$column] = $item['header'];
            }
        }

        return $unknown;
    }


    /**
     * Find the strongest field match.
     */
    protected function findBestMatch(
        string $header,
        array $usedFields
    ): ?array {
        $bestField = null;
        $bestScore = 0;


        foreach ($this->fields as $field => $aliases) {

            if (
                in_array(
                    $field,
                    $usedFields,
                    true
                )
            ) {
                continue;
            }


            foreach ($aliases as $alias) {

                $alias = $this->normalize($alias);

                if ($alias === null) {
                    continue;
                }


                $score = $this->calculateSimilarity(
                    $header,
                    $alias
                );


                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestField = $field;
                }
            }
        }


        /*
         * Exact and near-exact matches are accepted.
         *
         * 0.70 remains the minimum automatic-match threshold
         * for tolerant Excel files.
         */
        if (
            $bestField === null
            || $bestScore < 0.70
        ) {
            return null;
        }


        return [
            'field' => $bestField,
            'confidence' => round(
                $bestScore,
                2
            ),
        ];
    }


    /**
     * Calculate similarity between two normalized headers.
     */
    protected function calculateSimilarity(
        string $header,
        string $alias
    ): float {
        /*
         * Exact match.
         */
        if ($header === $alias) {
            return 1.0;
        }


        /*
         * Contains match.
         *
         * Example:
         * "product name arabic" contains "product name"
         */
        if (
            str_contains(
                $header,
                $alias
            )
            ||
            str_contains(
                $alias,
                $header
            )
        ) {

            $shorter = min(
                mb_strlen($header),
                mb_strlen($alias)
            );

            $longer = max(
                mb_strlen($header),
                mb_strlen($alias)
            );


            if ($longer === 0) {
                return 0;
            }


            return $shorter / $longer;
        }


        /*
         * General similarity.
         */
        similar_text(
            $header,
            $alias,
            $percentage
        );


        return $percentage / 100;
    }


    /**
     * Normalize a header for comparison.
     *
     * Handles:
     * - uppercase/lowercase
     * - underscores
     * - dashes
     * - duplicated whitespace
     * - line breaks
     * - common punctuation
     */
    protected function normalize(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }


        $value = trim(
            (string) $value
        );


        if ($value === '') {
            return null;
        }


        /*
         * Normalize line breaks/tabs first.
         */
        $value = str_replace(
            [
                "\r",
                "\n",
                "\t",
            ],
            ' ',
            $value
        );


        /*
         * Lowercase for language-independent
         * Latin comparison.
         */
        $value = mb_strtolower(
            $value
        );


        /*
         * Normalize separators.
         */
        $value = str_replace(
            [
                '_',
                '-',
                '–',
                '—',
                '/',
                '\\',
            ],
            ' ',
            $value
        );


        /*
         * Normalize common punctuation.
         */
        $value = str_replace(
            [
                '.',
                ':',
                ';',
                ',',
                '(',
                ')',
                '[',
                ']',
            ],
            ' ',
            $value
        );


        /*
         * Collapse whitespace.
         */
        $value = preg_replace(
            '/\s+/',
            ' ',
            $value
        );


        return trim(
            $value
        );
    }
}