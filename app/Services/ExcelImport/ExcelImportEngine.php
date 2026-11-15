<?php

namespace App\Services\ExcelImport;

class ExcelImportEngine
{
    public function __construct(
        protected ExcelAnalyzer $analyzer,
        protected ImportResult $result,
    ) {}

    /**
     * Analyze an Excel file and return the complete preview result.
     *
     * This method does NOT write anything to the database.
     */
    public function analyze(string $filePath): array
    {
        $analysis = $this->analyzer->analyze($filePath);

        return $this->result->build($analysis);
    }
}