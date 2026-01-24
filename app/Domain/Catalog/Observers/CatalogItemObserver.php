<?php

namespace App\Domain\Catalog\Observers;

use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Support\Str;

/**
 * Catalog Item Observer
 *
 * Handles CatalogItem model lifecycle events.
 */
class CatalogItemObserver
{
    /**
     * Handle the CatalogItem "creating" event.
     */
    public function creating(CatalogItem $catalogItem): void
    {
        // Generate slug if not set
        if (empty($catalogItem->slug)) {
            $catalogItem->slug = $this->generateSlug($catalogItem->name);
        }

        // Generate SKU if not set
        if (empty($catalogItem->sku)) {
            $catalogItem->sku = $this->generateSku($catalogItem);
        }

        // Normalize part number
        if (!empty($catalogItem->part_number)) {
            $catalogItem->part_number = $this->normalizePartNumber($catalogItem->part_number);
        }
    }

    /**
     * Handle the CatalogItem "updating" event.
     */
    public function updating(CatalogItem $catalogItem): void
    {
        // Update slug if name changed
        if ($catalogItem->isDirty('name') && empty($catalogItem->slug)) {
            $catalogItem->slug = $this->generateSlug($catalogItem->name);
        }

        // Normalize part number if changed
        if ($catalogItem->isDirty('part_number') && !empty($catalogItem->part_number)) {
            $catalogItem->part_number = $this->normalizePartNumber($catalogItem->part_number);
        }
    }

    /**
     * Handle the CatalogItem "deleted" event.
     */
    public function deleted(CatalogItem $catalogItem): void
    {
        // Soft delete related merchant items
        $catalogItem->merchantItems()->update(['status' => 0]);
    }

    /**
     * Generate unique slug
     */
    protected function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (CatalogItem::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Generate SKU
     */
    protected function generateSku(CatalogItem $catalogItem): string
    {
        $prefix = 'SKU';
        $brandCode = $catalogItem->brand_id ? str_pad($catalogItem->brand_id, 3, '0', STR_PAD_LEFT) : '000';
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$brandCode}-{$random}";
    }

    /**
     * Normalize part number
     */
    protected function normalizePartNumber(string $partNumber): string
    {
        // Remove spaces, dashes, and convert to uppercase
        return strtoupper(preg_replace('/[\s\-]/', '', $partNumber));
    }
}
