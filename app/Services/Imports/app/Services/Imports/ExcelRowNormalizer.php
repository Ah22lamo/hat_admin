<?php

namespace App\Services\Imports;

class ExcelRowNormalizer
{
    public function normalize(array $row): array
    {
        return [
            'item_no' => $this->string($row['item_no'] ?? null),

            'name' => $this->string($row['name'] ?? null),

            'category_code' => $this->categoryCode(
                $row['category_code'] ?? null
            ),

            'ctns' => $this->integer($row['ctns'] ?? null),

            'qty_per_ctn' => $this->integer(
                $row['qty_per_ctn'] ?? null
            ),

            'total_qty' => $this->integer(
                $row['total_qty'] ?? null
            ),

            'unit_price' => $this->decimal(
                $row['unit_price'] ?? null
            ),

            'subtotal' => $this->decimal(
                $row['subtotal'] ?? null
            ),

            'cbm' => $this->decimal(
                $row['cbm'] ?? null
            ),

            'total_cbm' => $this->decimal(
                $row['total_cbm'] ?? null
            ),

            'total_weight' => $this->decimal(
                $row['total_weight'] ?? null
            ),

            'pic' => $row['pic'] ?? null,
        ];
    }

    protected function string(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        /*
         * Excel error values are not valid product data.
         */
        if (str_starts_with($value, '#')) {
            return null;
        }

        return $value;
    }

    protected function categoryCode(mixed $value): ?string
    {
        $value = $this->string($value);

        if ($value === null) {
            return null;
        }

        return strtoupper($value);
    }

    protected function integer(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '' || str_starts_with($value, '#')) {
                return null;
            }

            $value = str_replace(',', '', $value);
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    protected function decimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '' || str_starts_with($value, '#')) {
                return null;
            }

            /*
             * Remove currency symbols and thousands separators.
             */
            $value = str_replace(
                ['￥', '¥', '$', '€', '£', ','],
                '',
                $value
            );
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
