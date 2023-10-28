<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

trait ImageUploadTrait
{
    /**
     * Upload an image file and return the file path.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @param string|null $fileName
     * @return string|null
     */
    public function uploadImage(UploadedFile $file, $folder, $fileName = null)
    {
        $fileName = $fileName ?: Str::random(25);
        $extension = $file->getClientOriginalExtension();
        $destinationPath = public_path($folder);
        $filePath = $folder . '/' . $fileName . '.' . $extension;

        $file->move($destinationPath, $fileName . '.' . $extension);

        return $filePath;
    }

    /**
     * Delete an image file.
     *
     * @param string $path
     * @return bool
     */
    public function deleteImage($path)
    {
        $filePath = public_path($path);

        if (file_exists($filePath)) {
            unlink($filePath);
            return true;
        }

        return false;
    }
}
