<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageUploadService
{
    public function store(UploadedFile $file, string $folder): string
    {
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $filename = Str::uuid()->toString() . '.' . $extension;
        $relativeDir = 'uploads/' . trim($folder, '/');
        $targetDir = public_path($relativeDir);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $file->move($targetDir, $filename);

        return $relativeDir . '/' . $filename;
    }

    public function delete(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        if (!str_starts_with($relativePath, 'uploads/')) {
            return;
        }

        $fullPath = public_path($relativePath);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
