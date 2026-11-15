<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class ExcelImageExtractor
{
    public function extractFromWorksheet(
        Worksheet $worksheet,
        string $directory = 'products/imports'
    ): array {
        $results = [];

        foreach ($worksheet->getDrawingCollection() as $drawing) {
            try {
                $results[] = $this->extractDrawing(
                    drawing: $drawing,
                    directory: $directory
                );
            } catch (Throwable $e) {
                $results[] = [
                    'success' => false,
                    'coordinate' => $this->safeCoordinate($drawing),
                    'coordinate2' => $this->safeCoordinate2($drawing),
                    'row' => $this->safeRow($drawing),
                    'column' => $this->safeColumn($drawing),
                    'path' => null,
                    'filename' => null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    protected function extractDrawing(
    BaseDrawing $drawing,
    string $directory
): array {
    $coordinate = $drawing->getCoordinates();
    $row = $this->rowFromCoordinate($coordinate);
    $column = $this->columnFromCoordinate($coordinate);
    $extension = $this->detectExtension($drawing);

    $filename = sprintf(
        '%s_%s_%s.%s',
        Str::uuid(),
        $row ?? 'unknown',
        $column ?? 'unknown',
        $extension
    );

    $relativePath = trim($directory, '/') . '/' . $filename;

    if ($drawing instanceof Drawing) {
        $sourcePath = $drawing->getPath();

        if (!$sourcePath) {
            throw new \RuntimeException(
                'مسار صورة Excel فارغ.'
            );
        }

        $contents = @file_get_contents($sourcePath);

        if ($contents === false || $contents === '') {
            throw new \RuntimeException(
                'تعذر قراءة الصورة المضمنة داخل ملف Excel.'
            );
        }

        $stored = Storage::disk('public')->put(
            $relativePath,
            $contents
        );

        if (!$stored) {
            throw new \RuntimeException(
                'تعذر حفظ صورة Excel في التخزين.'
            );
        }
    } elseif ($drawing instanceof MemoryDrawing) {
        $contents = $this->memoryDrawingToBinary($drawing);

        if ($contents === '') {
            throw new \RuntimeException(
                'الصورة المؤقتة داخل Excel فارغة.'
            );
        }

        $stored = Storage::disk('public')->put(
            $relativePath,
            $contents
        );

        if (!$stored) {
            throw new \RuntimeException(
                'تعذر حفظ الصورة المؤقتة في التخزين.'
            );
        }
    } else {
        throw new \RuntimeException(
            'نوع صورة Excel غير مدعوم: ' .
            get_class($drawing)
        );
    }

    return [
        'success' => true,
        'coordinate' => $coordinate,
        'coordinate2' => $this->safeCoordinate2($drawing),
        'row' => $row,
        'column' => $column,
        'offset_x' => $drawing->getOffsetX(),
        'offset_y' => $drawing->getOffsetY(),
        'offset_x2' => $drawing->getOffsetX2(),
        'offset_y2' => $drawing->getOffsetY2(),
        'width' => $drawing->getWidth(),
        'height' => $drawing->getHeight(),

        // PhpSpreadsheet version in this project
        // does not provide Drawing::isInCell().
        'is_in_cell' => true,

        'filename' => $filename,
        'path' => $relativePath,
        'extension' => $extension,
    ];
}

    protected function memoryDrawingToBinary(
        MemoryDrawing $drawing
    ): string {
        $resource = $drawing->getImageResource();

        if (!$resource) {
            throw new \RuntimeException(
                'Memory drawing لا يحتوي على مورد صورة.'
            );
        }

        ob_start();

        try {
            $renderingFunction = $drawing->getRenderingFunction();

            call_user_func(
                $renderingFunction,
                $resource
            );

            $contents = ob_get_contents();

            ob_end_clean();
        } catch (Throwable $e) {
            ob_end_clean();

            throw new \RuntimeException(
                'تعذر تحويل صورة MemoryDrawing.',
                0,
                $e
            );
        }

        if ($contents === false || $contents === '') {
            throw new \RuntimeException(
                'تعذر إنشاء بيانات الصورة.'
            );
        }

        return $contents;
    }

    protected function detectExtension(
        BaseDrawing $drawing
    ): string {
        if ($drawing instanceof Drawing) {
            $extension = strtolower(
                pathinfo(
                    $drawing->getPath(),
                    PATHINFO_EXTENSION
                )
            );

            if ($extension !== '') {
                return $extension;
            }
        }

        if ($drawing instanceof MemoryDrawing) {
            return match (
                strtolower($drawing->getMimeType())
            ) {
                'image/jpeg',
                'image/jpg' => 'jpg',

                'image/png' => 'png',

                'image/gif' => 'gif',

                'image/webp' => 'webp',

                default => 'png',
            };
        }

        return 'bin';
    }

    protected function rowFromCoordinate(
        ?string $coordinate
    ): ?int {
        if (!$coordinate) {
            return null;
        }

        if (!preg_match('/\d+$/', $coordinate, $matches)) {
            return null;
        }

        return (int) $matches[0];
    }

    protected function columnFromCoordinate(
        ?string $coordinate
    ): ?string {
        if (!$coordinate) {
            return null;
        }

        if (
            !preg_match(
                '/^[A-Z]+/',
                strtoupper($coordinate),
                $matches
            )
        ) {
            return null;
        }

        return $matches[0];
    }

    protected function safeCoordinate(
        BaseDrawing $drawing
    ): ?string {
        try {
            return $drawing->getCoordinates();
        } catch (Throwable) {
            return null;
        }
    }

    protected function safeCoordinate2(
        BaseDrawing $drawing
    ): ?string {
        try {
            return $drawing->getCoordinates2();
        } catch (Throwable) {
            return null;
        }
    }

    protected function safeRow(
        BaseDrawing $drawing
    ): ?int {
        return $this->rowFromCoordinate(
            $this->safeCoordinate($drawing)
        );
    }

    protected function safeColumn(
        BaseDrawing $drawing
    ): ?string {
        return $this->columnFromCoordinate(
            $this->safeCoordinate($drawing)
        );
    }
}