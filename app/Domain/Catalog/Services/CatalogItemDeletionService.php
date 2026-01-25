<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Service for catalog item deletion operations.
 * Handles cascading deletion of related records and files.
 */
class CatalogItemDeletionService
{
    /**
     * Delete a catalog item and all related data.
     *
     * @param int $catalogItemId
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $catalogItemId): void
    {
        $catalogItem = CatalogItem::findOrFail($catalogItemId);

        DB::transaction(function () use ($catalogItem) {
            // Delete related data in order
            $this->deleteMerchantPhotos($catalogItem);
            $this->deleteRelatedRecords($catalogItem);
            $this->deletePhotoFiles($catalogItem);

            // Finally delete the catalog item
            $catalogItem->delete();
        });
    }

    /**
     * Delete merchant photos and their files.
     */
    private function deleteMerchantPhotos(CatalogItem $catalogItem): void
    {
        $merchantPhotos = $catalogItem->merchantPhotos;

        if ($merchantPhotos->isEmpty()) {
            return;
        }

        foreach ($merchantPhotos as $photo) {
            $filePath = public_path('assets/images/merchant-photos/' . $photo->photo);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
            $photo->delete();
        }
    }

    /**
     * Delete all related records (flags, reviews, favorites, clicks, notes).
     */
    private function deleteRelatedRecords(CatalogItem $catalogItem): void
    {
        // Delete abuse flags
        $catalogItem->abuseFlags()->delete();

        // Delete catalog reviews
        $catalogItem->catalogReviews()->delete();

        // Delete favorites
        $catalogItem->favorites()->delete();

        // Delete clicks
        $catalogItem->clicks()->delete();

        // Delete buyer notes (with replies)
        $buyerNotes = $catalogItem->buyerNotes;
        foreach ($buyerNotes as $note) {
            $note->replies()->delete();
            $note->delete();
        }
    }

    /**
     * Delete photo files from storage.
     */
    private function deletePhotoFiles(CatalogItem $catalogItem): void
    {
        // Don't delete if photo is a URL
        if (!filter_var($catalogItem->photo, FILTER_VALIDATE_URL)) {
            if ($catalogItem->photo) {
                $photoPath = public_path('assets/images/catalogItems/' . $catalogItem->photo);
                if (File::exists($photoPath)) {
                    File::delete($photoPath);
                }
            }
        }

        // Delete thumbnail
        if ($catalogItem->thumbnail) {
            $thumbnailPath = public_path('assets/images/thumbnails/' . $catalogItem->thumbnail);
            if (File::exists($thumbnailPath)) {
                File::delete($thumbnailPath);
            }
        }
    }

    /**
     * Check if a catalog item can be safely deleted.
     * Returns any blocking conditions.
     *
     * @param int $catalogItemId
     * @return array ['can_delete' => bool, 'blockers' => array]
     */
    public function canDelete(int $catalogItemId): array
    {
        $catalogItem = CatalogItem::findOrFail($catalogItemId);
        $blockers = [];

        // Check for active merchant items
        $activeMerchantItems = $catalogItem->merchantItems()
            ->where('status', 1)
            ->count();

        if ($activeMerchantItems > 0) {
            $blockers[] = __(':count active merchant offers exist', ['count' => $activeMerchantItems]);
        }

        // Check for pending purchases (optional - could be made configurable)
        // $pendingPurchases = ...

        return [
            'can_delete' => empty($blockers),
            'blockers' => $blockers,
        ];
    }
}
