<?php

namespace App\Domain\Catalog\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * Has Images Trait
 *
 * Provides image management functionality.
 */
trait HasImages
{
    /**
     * Get images column
     */
    public function getImagesColumn(): string
    {
        return $this->imagesColumn ?? 'images';
    }

    /**
     * Get thumbnail column
     */
    public function getThumbnailColumn(): string
    {
        return $this->thumbnailColumn ?? 'thumbnail';
    }

    /**
     * Get storage disk
     */
    public function getImagesDisk(): string
    {
        return $this->imagesDisk ?? 'public';
    }

    /**
     * Get images path prefix
     */
    public function getImagesPath(): string
    {
        return $this->imagesPath ?? 'images';
    }

    /**
     * Get all images
     */
    public function getImages(): array
    {
        $images = $this->{$this->getImagesColumn()};

        if (is_string($images)) {
            $images = json_decode($images, true) ?? [];
        }

        return $images ?? [];
    }

    /**
     * Get first image
     */
    public function getFirstImage(): ?string
    {
        $images = $this->getImages();
        return $images[0] ?? null;
    }

    /**
     * Get thumbnail
     */
    public function getThumbnail(): ?string
    {
        return $this->{$this->getThumbnailColumn()} ?? $this->getFirstImage();
    }

    /**
     * Get image URL
     */
    public function getImageUrl(?string $image = null): ?string
    {
        $image = $image ?? $this->getThumbnail();

        if (!$image) {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return Storage::disk($this->getImagesDisk())->url($image);
    }

    /**
     * Get all image URLs
     */
    public function getImageUrls(): array
    {
        return array_map(fn($img) => $this->getImageUrl($img), $this->getImages());
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl(): ?string
    {
        return $this->getImageUrl($this->getThumbnail());
    }

    /**
     * Check if has images
     */
    public function hasImages(): bool
    {
        return count($this->getImages()) > 0;
    }

    /**
     * Get images count
     */
    public function getImagesCount(): int
    {
        return count($this->getImages());
    }

    /**
     * Add image
     */
    public function addImage(string $path): bool
    {
        $images = $this->getImages();
        $images[] = $path;

        return $this->update([$this->getImagesColumn() => json_encode($images)]);
    }

    /**
     * Remove image
     */
    public function removeImage(string $path): bool
    {
        $images = array_filter($this->getImages(), fn($img) => $img !== $path);

        return $this->update([$this->getImagesColumn() => json_encode(array_values($images))]);
    }

    /**
     * Set thumbnail
     */
    public function setThumbnail(string $path): bool
    {
        return $this->update([$this->getThumbnailColumn() => $path]);
    }

    /**
     * Scope with images
     */
    public function scopeWithImages($query)
    {
        return $query->whereNotNull($this->getImagesColumn())
            ->where($this->getImagesColumn(), '!=', '[]')
            ->where($this->getImagesColumn(), '!=', '');
    }

    /**
     * Scope without images
     */
    public function scopeWithoutImages($query)
    {
        return $query->where(function ($q) {
            $q->whereNull($this->getImagesColumn())
                ->orWhere($this->getImagesColumn(), '[]')
                ->orWhere($this->getImagesColumn(), '');
        });
    }
}
