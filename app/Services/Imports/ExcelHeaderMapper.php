<?php

namespace App\Services\Imports;

class ExcelHeaderMapper
{
    protected array $map = [
        'item no' => 'item_no',
        'item_no' => 'item_no',
        'item number' => 'item_no',
        'item_number' => 'item_no',
        'itemno' => 'item_no',

        'name' => 'name',
        'product name' => 'name',
        'product_name' => 'name',
        'item name' => 'name',
        'item_name' => 'name',

        'category code' => 'category_code',
        'category_code' => 'category_code',
        'category no' => 'category_code',
        'category_no' => 'category_code',
        'cat code' => 'category_code',
        'cat_code' => 'category_code',

        'pic' => 'pic',
        'picture' => 'pic',
        'image' => 'pic',

        'ctns' => 'ctns',
        'ctn' => 'ctns',
        'cartons' => 'ctns',

        'qty/ctn' => 'qty_per_ctn',
        'qty per ctn' => 'qty_per_ctn',
        'qty_per_ctn' => 'qty_per_ctn',

        't.qty' => 'total_qty',
        't qty' => 'total_qty',
        'total qty' => 'total_qty',
        'total_qty' => 'total_qty',

        'u.price' => 'unit_price',
        'u price' => 'unit_price',
        'unit price' => 'unit_price',
        'unit_price' => 'unit_price',

        'sub total' => 'subtotal',
        'subtotal' => 'subtotal',
        'sub_total' => 'subtotal',

        'cbm' => 'cbm',

        't.cbm' => 'total_cbm',
        't cbm' => 'total_cbm',
        'total cbm' => 'total_cbm',
        'total_cbm' => 'total_cbm',

        't.weght' => 'total_weight',
        't weight' => 'total_weight',
        't.weight' => 'total_weight',
        'total weight' => 'total_weight',
        'total_weight' => 'total_weight',
    ];

    public function normalize(string $header): string
    {
        $header = trim($header);

        $header = preg_replace('/\s+/', ' ', $header);

        $normalized = mb_strtolower($header);

        return $this->map[$normalized]
            ?? preg_replace('/[^a-z0-9]+/', '_', $normalized);
    }

    public function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $header => $value) {
            $key = $this->normalize((string) $header);

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}