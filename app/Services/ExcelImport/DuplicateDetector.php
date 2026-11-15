<?php

namespace App\Services\ExcelImport;

use App\Models\Product;

class DuplicateDetector
{
    /**
     * Detect duplicate ITEM numbers inside the uploaded Excel.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, array<int, int>>
     */
    public function detectWithinFile(array $rows): array
    {
        $items = [];

        foreach ($rows as $index => $row) {
            $itemNo = $this->normalizeItem(
                $row['item_no'] ?? null
            );

            if ($itemNo === null) {
                continue;
            }

            $items[$itemNo][] = $index;
        }

        return array_filter(
            $items,
            fn (array $indexes): bool => count($indexes) > 1
        );
    }

    /**
     * Detect ITEM numbers that already exist in the database.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, int>
     */
    public function detectExisting(array $rows): array
    {
        $itemNumbers = collect($rows)
            ->map(fn (array $row) => $this->normalizeItem(
                $row['item_no'] ?? null
            ))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($itemNumbers === []) {
            return [];
        }

        return Product::query()
            ->whereIn('item_no', $itemNumbers)
            ->pluck('id', 'item_no')
            ->all();
    }

    protected function normalizeItem(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}