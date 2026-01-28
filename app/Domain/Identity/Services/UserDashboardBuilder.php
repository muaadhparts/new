<?php

namespace App\Domain\Identity\Services;

use App\Domain\Identity\DTOs\UserDashboardDTO;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * User Dashboard Builder
 * Builds UserDashboardDTO from User model.
 * DATA_FLOW_POLICY: All logic here, View receives DTO only.
 */
class UserDashboardBuilder
{
    /**
     * Build dashboard DTO for a user
     */
    public function build(User $user): UserDashboardDTO
    {
        // Calculate stats
        $totalPurchases = $user->purchases()->count();
        $pendingPurchases = $user->purchases()->where('status', 'pending')->count();
        $completedPurchases = $user->purchases()->where('status', 'completed')->count();
        $favoritesCount = $user->favorites()->count();

        // Get recent purchases and transform to display arrays
        $recentPurchases = $user->purchases()
            ->latest()
            ->take(6)
            ->get()
            ->map(fn($purchase) => $this->buildPurchaseItem($purchase))
            ->toArray();

        // Build photo URL
        $userPhotoUrl = $user->photo
            ? (filter_var($user->photo, FILTER_VALIDATE_URL)
                ? $user->photo
                : Storage::url($user->photo))
            : asset('assets/images/noimage.png');

        // Get current currency for formatting
        $curr = monetaryUnit()->getCurrent();

        // Format financial values
        $affiliateBonusFormatted = \PriceHelper::showCurrencyPrice(($user->affilate_income ?? 0) * $curr->value);
        $walletBalanceFormatted = \PriceHelper::showCurrencyPrice(($user->balance ?? 0) * $curr->value);

        return new UserDashboardDTO(
            userId: $user->id,
            userName: $user->name,
            userEmail: $user->email,
            userPhoto: $user->photo,
            userPhotoUrl: $userPhotoUrl,
            userPhone: $user->phone,
            memberSince: $user->created_at?->format('d M Y') ?? 'N/A',
            isMerchant: $user->is_merchant ?? 0,
            totalPurchases: $totalPurchases,
            pendingPurchases: $pendingPurchases,
            completedPurchases: $completedPurchases,
            favoritesCount: $favoritesCount,
            affiliateBonusFormatted: $affiliateBonusFormatted,
            walletBalanceFormatted: $walletBalanceFormatted,
            recentPurchases: $recentPurchases,
        );
    }

    /**
     * Build a single purchase item for display
     */
    private function buildPurchaseItem($purchase): array
    {
        $statusClass = match ($purchase->status) {
            'pending', 'processing' => 'yellow-btn',
            'completed' => 'green-btn',
            'declined' => 'red-btn',
            default => 'black-btn',
        };

        return [
            'id' => $purchase->id,
            'purchase_number' => $purchase->purchase_number,
            'status' => $purchase->status,
            'status_class' => $statusClass,
            'status_label' => __($purchase->status),
            'created_at' => $purchase->created_at?->format('d M Y'),
            'total_formatted' => \App\Domain\Catalog\Models\CatalogItem::convertPrice($purchase->pay_amount ?? 0),
            'details_url' => route('user-purchase', $purchase->id),
        ];
    }
}
