<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function replace(?UploadedFile $file, ?string $old, string $directory): ?string
    {
        if (! $file) {
            return $old;
        } if ($old) {
            Storage::disk('public')->delete($old);
        }

        return $file->store($directory, 'public');
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
