<?php

namespace App\Services\ExcelImport;

class DataValidator
{
    public function validate(array $row): array
    {
        $errors = [];
        $warnings = [];

        /*
         * Required business identifier.
         */
        if (
            !isset($row['item_no']) ||
            trim((string) $row['item_no']) === ''
        ) {
            $errors[] = 'رقم الصنف ITEM مفقود.';
        }

        /*
         * Normalize numeric values before validation.
         */
        $cartons = $this->number($row['cartons_count'] ?? null);
        $qtyPerCarton = $this->number($row['qty_per_carton'] ?? null);
        $totalQuantity = $this->number($row['total_quantity'] ?? null);

        $unitPrice = $this->number($row['unit_price'] ?? null);
        $amount = $this->number($row['subtotal_price'] ?? null);

        $cbm = $this->number($row['cbm_per_carton'] ?? null);
        $totalCbm = $this->number($row['total_cbm'] ?? null);

        /*
         * Numeric sanity checks.
         */
        $numericFields = [
            'cartons_count' => $cartons,
            'qty_per_carton' => $qtyPerCarton,
            'total_quantity' => $totalQuantity,
            'unit_price' => $unitPrice,
            'subtotal_price' => $amount,
            'cbm_per_carton' => $cbm,
            'total_cbm' => $totalCbm,
        ];

        foreach ($numericFields as $field => $value) {
            if ($value !== null && $value < 0) {
                $errors[] = $this->fieldLabel($field)
                    . ' لا يمكن أن تكون قيمة سالبة.';
            }
        }

        /*
         * CTNS × PCS/CTN = QTY
         */
        if (
            $cartons !== null &&
            $qtyPerCarton !== null &&
            $totalQuantity !== null
        ) {
            $expectedQuantity = $cartons * $qtyPerCarton;

            if (!$this->approximatelyEqual($expectedQuantity, $totalQuantity)) {
                $warnings[] =
                    "QTY لا تطابق CTNS × PCS/CTN. "
                    . "المتوقع: {$expectedQuantity}، "
                    . "الموجود: {$totalQuantity}.";
            }
        }

        /*
         * CTNS × CBM = T/CBM
         */
        if (
            $cartons !== null &&
            $cbm !== null &&
            $totalCbm !== null
        ) {
            $expectedCbm = $cartons * $cbm;

            if (!$this->approximatelyEqual($expectedCbm, $totalCbm, 0.01)) {
                $warnings[] =
                    "T/CBM لا تطابق CTNS × CBM. "
                    . "المتوقع تقريباً: "
                    . $this->formatNumber($expectedCbm)
                    . "، الموجود: "
                    . $this->formatNumber($totalCbm)
                    . '.';
            }
        }

        /*
         * QTY × PRICE = AMOUNT
         */
        if (
            $totalQuantity !== null &&
            $unitPrice !== null &&
            $amount !== null
        ) {
            $expectedAmount = $totalQuantity * $unitPrice;

            if (!$this->approximatelyEqual($expectedAmount, $amount, 0.01)) {
                $warnings[] =
                    "AMOUNT لا تطابق QTY × PRICE. "
                    . "المتوقع: "
                    . $this->formatNumber($expectedAmount)
                    . "، الموجود: "
                    . $this->formatNumber($amount)
                    . '.';
            }
        }

        /*
         * Missing commercial data is not automatically an error.
         * The source Excel may legitimately omit it.
         */
        if ($unitPrice === null) {
            $warnings[] = 'سعر الوحدة PRICE غير موجود.';
        }

        if ($amount === null) {
            $warnings[] = 'الإجمالي AMOUNT غير موجود.';
        }

        /*
         * Determine final status.
         */
        $status = match (true) {
            count($errors) > 0 => 'failed',
            count($warnings) > 0 => 'success_with_warning',
            default => 'success',
        };

        return [
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    protected function number(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);

        /*
         * Remove currency symbols and non-numeric text.
         */
        $value = preg_replace('/[^\d.,\-]/u', '', $value);

        if ($value === '') {
            return null;
        }

        /*
         * Handle:
         * 21,600.00
         * 21.600,00
         * 21600
         * 18.50
         */
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $lastComma = strrpos($value, ',');
            $lastDot = strrpos($value, '.');

            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif (str_contains($value, ',')) {
            $parts = explode(',', $value);

            if (
                count($parts) === 2 &&
                strlen($parts[1]) <= 2
            ) {
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        }

        return is_numeric($value)
            ? (float) $value
            : null;
    }

    protected function approximatelyEqual(
        float $expected,
        float $actual,
        float $tolerance = 0.01
    ): bool {
        return abs($expected - $actual) <= $tolerance;
    }

    protected function formatNumber(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    protected function fieldLabel(string $field): string
    {
        return match ($field) {
            'cartons_count' => 'CTNS',
            'qty_per_carton' => 'PCS/CTN',
            'total_quantity' => 'QTY',
            'unit_price' => 'PRICE',
            'subtotal_price' => 'AMOUNT',
            'cbm_per_carton' => 'CBM',
            'total_cbm' => 'T/CBM',
            default => $field,
        };
    }
}