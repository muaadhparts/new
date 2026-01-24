<?php

namespace App\Domain\Catalog\Policies;

use App\Models\User;
use App\Models\CatalogReview;
use App\Models\CatalogItem;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Catalog Review Policy
 *
 * Determines authorization for product review actions.
 */
class CatalogReviewPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view reviews
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view reviews
        return true;
    }

    /**
     * Determine if user can view specific review
     */
    public function view(?User $user, CatalogReview $review): bool
    {
        // Anyone can view approved reviews
        if ($review->status === 'approved') {
            return true;
        }

        // Owner can view their own review
        return $user && $user->id === $review->user_id;
    }

    /**
     * Determine if user can create review
     */
    public function create(User $user, CatalogItem $item): bool
    {
        // Must be logged in
        if (!$user) {
            return false;
        }

        // Check if user has purchased this item
        $hasPurchased = $user->purchases()
            ->where('status', 'completed')
            ->whereJsonContains('cart', [['catalog_item_id' => $item->id]])
            ->exists();

        if (!$hasPurchased) {
            return false;
        }

        // Check if user already reviewed
        $alreadyReviewed = CatalogReview::where('user_id', $user->id)
            ->where('catalog_item_id', $item->id)
            ->exists();

        return !$alreadyReviewed;
    }

    /**
     * Determine if user can update their review
     */
    public function update(User $user, CatalogReview $review): bool
    {
        // Must be owner
        if ($user->id !== $review->user_id) {
            return false;
        }

        // Can only update within edit window (e.g., 7 days)
        $editWindow = config('catalog.review_edit_window_days', 7);
        return $review->created_at->diffInDays(now()) <= $editWindow;
    }

    /**
     * Determine if user can delete their review
     */
    public function delete(User $user, CatalogReview $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * Determine if user can report a review
     */
    public function report(User $user, CatalogReview $review): bool
    {
        // Cannot report own review
        return $user->id !== $review->user_id;
    }

    /**
     * Determine if user can reply to review (merchants only)
     */
    public function reply(User $user, CatalogReview $review): bool
    {
        // Must be a merchant who sells this item
        if ($user->role !== 'merchant') {
            return false;
        }

        // Check if merchant has this item
        return $user->merchantItems()
            ->where('catalog_item_id', $review->catalog_item_id)
            ->exists();
    }
}
