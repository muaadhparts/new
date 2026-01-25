<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\CatalogReview;
use Illuminate\Support\Facades\Auth;

/**
 * AddReviewAction - Add review to catalog item
 *
 * Single-responsibility action for adding product reviews.
 */
class AddReviewAction
{
    /**
     * Execute the action
     *
     * @param int $catalogItemId Catalog item ID
     * @param int $rating Rating (1-5)
     * @param string|null $comment Review comment
     * @param int|null $userId User ID (defaults to current user)
     * @return array{success: bool, message: string, review?: CatalogReview}
     */
    public function execute(
        int $catalogItemId,
        int $rating,
        ?string $comment = null,
        ?int $userId = null
    ): array {
        $userId = $userId ?? Auth::id();

        if (!$userId) {
            return [
                'success' => false,
                'message' => __('Please login to add reviews'),
            ];
        }

        // Validate rating
        if ($rating < 1 || $rating > 5) {
            return [
                'success' => false,
                'message' => __('Rating must be between 1 and 5'),
            ];
        }

        $catalogItem = CatalogItem::find($catalogItemId);

        if (!$catalogItem) {
            return [
                'success' => false,
                'message' => __('Item not found'),
            ];
        }

        // Check if user already reviewed this item
        $existingReview = CatalogReview::where('user_id', $userId)
            ->where('catalog_item_id', $catalogItemId)
            ->first();

        if ($existingReview) {
            // Update existing review
            $existingReview->rating = $rating;
            $existingReview->comment = $comment;
            $existingReview->save();

            return [
                'success' => true,
                'message' => __('Review updated'),
                'review' => $existingReview->fresh(),
            ];
        }

        // Create new review
        $review = CatalogReview::create([
            'user_id' => $userId,
            'catalog_item_id' => $catalogItemId,
            'rating' => $rating,
            'comment' => $comment,
            'status' => 1, // Pending approval
        ]);

        return [
            'success' => true,
            'message' => __('Review submitted'),
            'review' => $review,
        ];
    }

    /**
     * Delete a review
     *
     * @param int $reviewId Review ID
     * @param int|null $userId User ID (for ownership check)
     * @return array
     */
    public function delete(int $reviewId, ?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        $review = CatalogReview::find($reviewId);

        if (!$review) {
            return [
                'success' => false,
                'message' => __('Review not found'),
            ];
        }

        // Check ownership (unless admin)
        if ($review->user_id !== $userId) {
            return [
                'success' => false,
                'message' => __('Unauthorized'),
            ];
        }

        $review->delete();

        return [
            'success' => true,
            'message' => __('Review deleted'),
        ];
    }
}
