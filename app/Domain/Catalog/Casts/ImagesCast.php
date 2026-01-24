<?php

namespace App\Domain\Catalog\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Images Cast
 *
 * Casts image data with URL generation support.
 */
class ImagesCast implements CastsAttributes
{
    /**
     * Storage disk
     */
    protected string $disk;

    /**
     * Path prefix
     */
    protected string $prefix;

    /**
     * Create a new cast instance.
     */
    public function __construct(string $disk = 'public', string $prefix = '')
    {
        $this->disk = $disk;
        $this->prefix = $prefix;
    }

    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $images = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($images)) {
            return [];
        }

        return array_map(function ($image) {
            if (is_string($image)) {
                return [
                    'path' => $image,
                    'url' => $this->generateUrl($image),
                ];
            }

            return [
                'path' => $image['path'] ?? $image,
                'url' => $this->generateUrl($image['path'] ?? $image),
                'alt' => $image['alt'] ?? null,
                'sort' => $image['sort'] ?? 0,
            ];
        }, $images);
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === []) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        // Extract just the paths for storage
        $paths = array_map(function ($image) {
            return is_array($image) ? ($image['path'] ?? $image) : $image;
        }, $value);

        return json_encode($paths, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Generate URL for image
     */
    protected function generateUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $fullPath = $this->prefix ? trim($this->prefix, '/') . '/' . $path : $path;

        return \Storage::disk($this->disk)->url($fullPath);
    }
}
