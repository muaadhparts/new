<?php

namespace App\Domain\Merchant\Services;

use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * MerchantItemImageService - Centralized image management for merchant items
 *
 * Handles all photo operations for merchant items.
 */
class MerchantItemImageService
{
    private const UPLOAD_PATH = 'assets/images/products/';
    private const MAX_WIDTH = 1200;
    private const MAX_HEIGHT = 1200;
    private const QUALITY = 85;

    /**
     * Upload photo for merchant item
     */
    public function uploadPhoto(MerchantItem $item, UploadedFile $file): MerchantPhoto
    {
        $filename = $this->generateFilename($file);
        $path = public_path(self::UPLOAD_PATH . $filename);

        // Resize and optimize image
        $image = Image::make($file);
        $image->resize(self::MAX_WIDTH, self::MAX_HEIGHT, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $image->save($path, self::QUALITY);

        // Check if this is the first photo (make it primary)
        $isPrimary = $item->photos()->count() === 0;

        return MerchantPhoto::create([
            'merchant_item_id' => $item->id,
            'photo' => $filename,
            'is_primary' => $isPrimary,
        ]);
    }

    /**
     * Delete photo
     */
    public function deletePhoto(MerchantPhoto $photo): bool
    {
        $path = public_path(self::UPLOAD_PATH . $photo->photo);

        if (file_exists($path)) {
            unlink($path);
        }

        return $photo->delete();
    }

    /**
     * Set photo as primary
     */
    public function setPrimaryPhoto(MerchantItem $item, int $photoId): void
    {
        // Remove primary flag from all photos
        $item->photos()->update(['is_primary' => false]);

        // Set new primary
        $item->photos()->where('id', $photoId)->update(['is_primary' => true]);
    }

    /**
     * Delete all photos for item
     */
    public function deleteAllPhotos(MerchantItem $item): void
    {
        foreach ($item->photos as $photo) {
            $this->deletePhoto($photo);
        }
    }

    /**
     * Get primary photo URL
     */
    public function getPrimaryPhotoUrl(MerchantItem $item): ?string
    {
        $photo = $item->primaryPhoto;

        if (!$photo) {
            return null;
        }

        return asset(self::UPLOAD_PATH . $photo->photo);
    }

    /**
     * Get all photos URLs
     */
    public function getAllPhotosUrls(MerchantItem $item): array
    {
        return $item->photos->map(function ($photo) {
            return [
                'id' => $photo->id,
                'url' => asset(self::UPLOAD_PATH . $photo->photo),
                'is_primary' => $photo->is_primary,
            ];
        })->toArray();
    }

    /**
     * Generate unique filename
     */
    private function generateFilename(UploadedFile $file): string
    {
        return time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
    }

    /**
     * Validate image file
     */
    public function validateImage(UploadedFile $file): bool
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($extension, $allowedExtensions) && $file->getSize() <= $maxSize;
    }
}
