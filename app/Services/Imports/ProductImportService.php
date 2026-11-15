<?php

namespace App\Services\Imports;

use App\Imports\ProductsImport;
use App\Models\ImportBatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProductImportService
{
    public function import(UploadedFile $file, int $userId): ImportBatch
    {
        $path = $file->store('imports/products', 'local');

        $batch = ImportBatch::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'import_type' => 'products',
            'total_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'status' => 'pending',
            'imported_by' => $userId,
        ]);

        try {
            $batch->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            Excel::import(
                new ProductsImport($batch),
                $path,
                'local'
            );

            $this->finalizeBatch($batch);

            return $batch;
        } catch (\Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    protected function finalizeBatch(ImportBatch $batch): void
    {
        $total = $batch->items()->count();

        $successful = $batch->items()
            ->whereIn('status', [
                'success',
                'success_with_warning',
            ])
            ->count();

        $failed = $batch->items()
            ->where('status', 'failed')
            ->count();

        $batch->update([
            'total_rows' => $total,
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'status' => $failed > 0
                ? 'completed_with_errors'
                : 'completed',
            'completed_at' => now(),
        ]);
    }
    
}