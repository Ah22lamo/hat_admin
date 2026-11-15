<?php

namespace App\Services\ExcelImport;

class DataNormalizer
{
    public function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $key = $this->normalizeKey($key);

            $normalized[$key] = $this->normalizeValue($value);
        }

        return $normalized;
    }

    public function normalizeKey(mixed $key): string
    {
        $key = trim((string) $key);

        $key = preg_replace('/\s+/', ' ', $key);

        return mb_strtolower($key);
    }

    public function normalizeValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }
        }

        return $value;
    }

    public function normalizeNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);

        // Remove currency symbols and text.
        $value = preg_replace('/[^\d.,\-]/u', '', $value);

        if ($value === '') {
            return null;
        }

        /*
         * Handle:
         * 21,600.00
         * 21.600,00
         * 21600
         * 18.00
         */
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $lastComma = strrpos($value, ',');
            $lastDot = strrpos($value, '.');

            if ($lastComma > $lastDot) {
                // European format: 21.600,00
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                // Standard format: 21,600.00
                $value = str_replace(',', '', $value);
            }
        } elseif (str_contains($value, ',')) {
            $parts = explode(',', $value);

            if (
                count($parts) === 2 &&
                strlen($parts[1]) <= 2
            ) {
                // Example: 18,50
                $value = str_replace(',', '.', $value);
            } else {
                // Example: 21,600
                $value = str_replace(',', '', $value);
            }
        }

        return is_numeric($value)
            ? (float) $value
            : null;
    }

    public function normalizeItemNumber(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        /*
         * Preserve leading zeros:
         * 015  → 015
         * 0380 → 0380
         */
        return $value !== '' ? $value : null;
    }
}