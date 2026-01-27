<?php

namespace App\Domain\Identity\DTOs;

/**
 * User Dashboard DTO
 * Contains all pre-computed data for the user dashboard view.
 * DATA_FLOW_POLICY: View receives this DTO only, no Models.
 */
final class UserDashboardDTO
{
    public function __construct(
        // User Info
        public readonly int $userId,
        public readonly string $userName,
        public readonly string $userEmail,
        public readonly ?string $userPhoto,
        public readonly string $userPhotoUrl,
        public readonly ?string $userPhone,
        public readonly string $memberSince,
        public readonly int $isMerchant,

        // Dashboard Stats
        public readonly int $totalPurchases,
        public readonly int $pendingPurchases,
        public readonly int $completedPurchases,
        public readonly int $favoritesCount,

        // Financial
        public readonly string $affiliateBonusFormatted,
        public readonly string $walletBalanceFormatted,

        // Recent Purchases (array of PurchaseListItemDTO-like arrays)
        public readonly array $recentPurchases,
    ) {}
}
