<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Image;

/**
 * ImageService - Handles image uploads to DigitalOcean Spaces
 *
 * All image operations should go through this service.
 * Uses 'do' disk for DigitalOcean Spaces storage.
 */
class ImageService
{
    protected string $disk = 'do';

    /**
     * Upload catalog item image (photo or thumbnail)
     *
     * @param UploadedFile $file
     * @param int $catalogItemId
     * @param string $partNumber
     * @param string $type 'photo' or 'thumbnail'
     * @return string The stored path
     */
    public function uploadCatalogImage(UploadedFile $file, int $catalogItemId, string $partNumber, string $type): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';

        // Resize based on type
        $width = $type === 'thumbnail' ? 300 : 800;
        $height = $type === 'thumbnail' ? 300 : 800;

        $img = Image::make($file->getRealPath())->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Generate filename: partNumber_id_type.extension
        // Example: 90311-47013_679167_photo.jpg
        $cleanPartNumber = preg_replace('/[^a-zA-Z0-9\-]/', '', $partNumber);
        $filename = $cleanPartNumber . '_' . $catalogItemId . '_' . $type . '.' . $extension;
        $path = 'catalog-items/' . $filename;

        // Store to DO Spaces
        Storage::disk($this->disk)->put($path, (string) $img->encode(), 'public');

        return $path;
    }

    /**
     * Upload merchant photo
     *
     * @param UploadedFile $file
     * @param int $merchantItemId
     * @param string|null $partNumber
     * @return string The stored path
     */
    public function uploadMerchantPhoto(UploadedFile $file, int $merchantItemId, ?string $partNumber = null): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';

        // Resize to standard size
        $img = Image::make($file->getRealPath())->resize(800, 800, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Generate filename with part_number
        // Example: 90311-47013_42833_1737012345.jpg
        $cleanPartNumber = $partNumber ? preg_replace('/[^a-zA-Z0-9\-]/', '', $partNumber) : '';
        $filename = ($cleanPartNumber ? $cleanPartNumber . '_' : '') . $merchantItemId . '_' . time() . '.' . $extension;
        $path = 'merchant-photos/' . $filename;

        // Store to DO Spaces
        Storage::disk($this->disk)->put($path, (string) $img->encode(), 'public');

        return $path;
    }

    /**
     * Delete an image from storage
     *
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        // If it's a full URL, extract the path
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $path = parse_url($path, PHP_URL_PATH);
            $path = ltrim($path, '/');
        }

        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Get the full URL for an image path
     *
     * @param string|null $path
     * @return string|null
     */
    public function getUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // If already a full URL, return as-is
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Check if an image exists
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        return Storage::disk($this->disk)->exists($path);
    }
}
