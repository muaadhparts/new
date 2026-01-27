<?php

namespace App\Domain\Merchant\Services;

use App\Domain\Identity\Models\User;
use App\Domain\Merchant\DTOs\MerchantCardDTO;
use Illuminate\Support\Collection;

/**
 * MerchantDisplayService - Centralized formatting for merchant display
 *
 * API-Ready: All formatting in one place for Web and API consumption.
 * DATA FLOW POLICY: Controller → Service → DTO → View/API
 *
 * @see docs/rules/DATA_FLOW_POLICY.md
 */
class MerchantDisplayService
{
    /**
     * Merchant verification statuses
     */
    public const STATUS_PENDING = 1;
    public const STATUS_VERIFIED = 2;
    public const STATUS_SUSPENDED = 0;

    /**
     * Merchant status display configuration
     */
    private const STATUS_CONFIG = [
        0 => [
            'label_key' => 'Suspended',
            'class' => 'bg-danger text-white',
            'color' => 'danger',
            'icon' => 'fa-ban',
            'badge_class' => 'm-badge--danger',
        ],
        1 => [
            'label_key' => 'Pending',
            'class' => 'bg-warning text-dark',
            'color' => 'warning',
            'icon' => 'fa-clock',
            'badge_class' => 'm-badge--warning',
        ],
        2 => [
            'label_key' => 'Verified',
            'class' => 'bg-success text-white',
            'color' => 'success',
            'icon' => 'fa-check-circle',
            'badge_class' => 'm-badge--success',
        ],
    ];

    // =========================================================================
    // MERCHANT NAME FORMATTING
    // =========================================================================

    /**
     * Get localized shop name
     */
    public function getShopName(User|array $merchant): string
    {
        $isAr = app()->getLocale() === 'ar';

        if (is_array($merchant)) {
            $shopNameAr = trim($merchant['shop_name_ar'] ?? '');
            $shopName = trim($merchant['shop_name'] ?? '');
            $name = trim($merchant['name'] ?? '');
        } else {
            $shopNameAr = trim($merchant->shop_name_ar ?? '');
            $shopName = trim($merchant->shop_name ?? '');
            $name = trim($merchant->name ?? '');
        }

        if ($isAr && $shopNameAr !== '') {
            return $shopNameAr;
        }

        return $shopName !== '' ? $shopName : ($name !== '' ? $name : __('Unknown Merchant'));
    }

    /**
     * Get merchant display name with quality brand (for product cards)
     */
    public function getDisplayNameWithBrand($merchantItem): string
    {
        if (!$merchantItem || !$merchantItem->user) {
            return '';
        }

        $displayName = $this->getShopName($merchantItem->user);

        if ($merchantItem->qualityBrand) {
            $brandName = $this->getLocalizedBrandName($merchantItem->qualityBrand);
            if ($brandName) {
                $displayName .= ' (' . $brandName . ')';
            }
        }

        return $displayName;
    }

    /**
     * Get localized quality brand name
     */
    public function getLocalizedBrandName($brand): string
    {
        if (!$brand) {
            return '';
        }

        $isAr = app()->getLocale() === 'ar';

        if (is_array($brand)) {
            $nameAr = trim($brand['name_ar'] ?? '');
            $nameEn = trim($brand['name_en'] ?? $brand['name'] ?? '');
        } else {
            $nameAr = trim($brand->name_ar ?? '');
            $nameEn = trim($brand->name_en ?? $brand->name ?? '');
        }

        if ($isAr && $nameAr !== '') {
            return $nameAr;
        }

        return $nameEn;
    }

    // =========================================================================
    // MERCHANT STATUS FORMATTING
    // =========================================================================

    /**
     * Get localized status label
     */
    public function getStatusLabel(int $status): string
    {
        $config = self::STATUS_CONFIG[$status] ?? null;
        return $config ? __($config['label_key']) : __('Unknown');
    }

    /**
     * Get CSS class for status badge
     */
    public function getStatusClass(int $status): string
    {
        return self::STATUS_CONFIG[$status]['class'] ?? 'bg-secondary text-white';
    }

    /**
     * Get status color name
     */
    public function getStatusColor(int $status): string
    {
        return self::STATUS_CONFIG[$status]['color'] ?? 'secondary';
    }

    /**
     * Get status icon class
     */
    public function getStatusIcon(int $status): string
    {
        return self::STATUS_CONFIG[$status]['icon'] ?? 'fa-circle';
    }

    /**
     * Get full status display data
     */
    public function getStatusDisplay(int $status): array
    {
        $config = self::STATUS_CONFIG[$status] ?? [
            'label_key' => 'Unknown',
            'class' => 'bg-secondary text-white',
            'color' => 'secondary',
            'icon' => 'fa-circle',
            'badge_class' => 'm-badge--secondary',
        ];

        return [
            'status' => $status,
            'label' => __($config['label_key']),
            'class' => $config['class'],
            'color' => $config['color'],
            'icon' => $config['icon'],
            'badge_class' => $config['badge_class'],
            'is_verified' => $status === self::STATUS_VERIFIED,
            'is_pending' => $status === self::STATUS_PENDING,
            'is_suspended' => $status === self::STATUS_SUSPENDED,
        ];
    }

    // =========================================================================
    // VERIFICATION BADGE
    // =========================================================================

    /**
     * Get verification badge data
     */
    public function getVerificationBadge(User|array $merchant): array
    {
        $status = is_array($merchant)
            ? (int) ($merchant['is_merchant'] ?? 0)
            : (int) ($merchant->is_merchant ?? 0);

        $isVerified = $status === self::STATUS_VERIFIED;

        return [
            'is_verified' => $isVerified,
            'label' => $isVerified ? __('Verified Merchant') : __('Pending Verification'),
            'icon' => $isVerified ? 'fa-check-circle' : 'fa-clock',
            'class' => $isVerified ? 'text-success' : 'text-warning',
            'tooltip' => $isVerified
                ? __('This merchant has been verified by our team')
                : __('This merchant is awaiting verification'),
        ];
    }

    // =========================================================================
    // MERCHANT PROFILE DATA
    // =========================================================================

    /**
     * Build display data for merchant profile/shop page
     */
    public function forProfile(User $merchant): array
    {
        $status = (int) ($merchant->is_merchant ?? 0);

        return [
            'id' => $merchant->id,
            'shop_name' => $this->getShopName($merchant),
            'owner_name' => $merchant->owner_name ?? $merchant->name,
            'slug' => $merchant->shop_slug ?? '',

            // Contact info
            'email' => $merchant->email,
            'phone' => $merchant->shop_number ?? $merchant->phone,
            'address' => $merchant->shop_address,
            'city' => $merchant->city,
            'country' => $merchant->country,
            'zip' => $merchant->zip,

            // Shop details
            'description' => $this->getLocalizedDescription($merchant),
            'message' => $merchant->shop_message,

            // Images
            'photo_url' => $this->getPhotoUrl($merchant),
            'logo_url' => $this->getLogoUrl($merchant),
            'banner_url' => $this->getBannerUrl($merchant),

            // Status
            'status' => $this->getStatusDisplay($status),
            'verification' => $this->getVerificationBadge($merchant),

            // Stats
            'member_since' => $merchant->created_at?->format('Y') ?? '',
            'member_since_formatted' => $merchant->created_at?->format('M Y') ?? __('N/A'),

            // URLs
            'shop_url' => route('front.merchant', $merchant->shop_slug ?? $merchant->id),
        ];
    }

    /**
     * Build display data for merchant card (listings)
     */
    public function forCard(User $merchant): MerchantCardDTO
    {
        return MerchantCardDTO::fromModel($merchant);
    }

    /**
     * Build display data for merchant list (admin/operator)
     */
    public function forList(User $merchant): array
    {
        $status = (int) ($merchant->is_merchant ?? 0);

        return [
            'id' => $merchant->id,
            'shop_name' => $this->getShopName($merchant),
            'owner_name' => $merchant->owner_name ?? $merchant->name,
            'email' => $merchant->email,
            'phone' => $merchant->shop_number ?? $merchant->phone,
            'city' => $merchant->city,
            'status' => $status,
            'status_label' => $this->getStatusLabel($status),
            'status_class' => $this->getStatusClass($status),
            'is_verified' => $status === self::STATUS_VERIFIED,
            'items_count' => (int) ($merchant->merchant_items_count ?? 0),
            'created_at' => $merchant->created_at?->format('Y-m-d'),
            'photo_url' => $this->getPhotoUrl($merchant),
            'shop_url' => route('front.merchant', $merchant->shop_slug ?? $merchant->id),
            'edit_url' => route('operator.merchant.edit', $merchant->id),
        ];
    }

    // =========================================================================
    // DASHBOARD STATS
    // =========================================================================

    /**
     * Build dashboard stats display data for merchant
     */
    public function forDashboard(User $merchant, array $stats = []): array
    {
        return [
            'shop_name' => $this->getShopName($merchant),
            'verification' => $this->getVerificationBadge($merchant),

            // Balance
            'current_balance' => monetaryUnit()->format($stats['current_balance'] ?? 0),
            'total_earning' => monetaryUnit()->format($stats['total_earning'] ?? 0),
            'pending_balance' => monetaryUnit()->format($stats['pending_balance'] ?? 0),

            // Counts
            'total_items' => (int) ($stats['total_items'] ?? 0),
            'total_purchases' => (int) ($stats['total_purchases'] ?? 0),
            'pending_purchases' => (int) ($stats['pending_purchases'] ?? 0),
            'completed_purchases' => (int) ($stats['completed_purchases'] ?? 0),

            // Reviews
            'average_rating' => round((float) ($stats['average_rating'] ?? 0), 1),
            'total_reviews' => (int) ($stats['total_reviews'] ?? 0),
        ];
    }

    // =========================================================================
    // IMAGE URLS
    // =========================================================================

    /**
     * Get merchant photo URL
     */
    public function getPhotoUrl(User|array $merchant): string
    {
        $photo = is_array($merchant)
            ? ($merchant['photo'] ?? null)
            : ($merchant->photo ?? null);

        if (!$photo) {
            return asset('assets/images/avatar.png');
        }

        if (filter_var($photo, FILTER_VALIDATE_URL)) {
            return $photo;
        }

        return \Storage::url($photo);
    }

    /**
     * Get merchant logo URL
     */
    public function getLogoUrl(User|array $merchant): string
    {
        $logo = is_array($merchant)
            ? ($merchant['merchant_logo'] ?? null)
            : ($merchant->merchant_logo ?? null);

        if (!$logo) {
            return $this->getPhotoUrl($merchant);
        }

        if (filter_var($logo, FILTER_VALIDATE_URL)) {
            return $logo;
        }

        return \Storage::url($logo);
    }

    /**
     * Get merchant shop banner URL
     */
    public function getBannerUrl(User|array $merchant): ?string
    {
        $banner = is_array($merchant)
            ? ($merchant['shop_image'] ?? null)
            : ($merchant->shop_image ?? null);

        if (!$banner) {
            return null;
        }

        if (filter_var($banner, FILTER_VALIDATE_URL)) {
            return $banner;
        }

        return \Storage::url($banner);
    }

    // =========================================================================
    // LOCALIZATION HELPERS
    // =========================================================================

    /**
     * Get localized shop description
     */
    protected function getLocalizedDescription(User $merchant): ?string
    {
        $isAr = app()->getLocale() === 'ar';

        if ($isAr && !empty($merchant->shop_description_ar)) {
            return $merchant->shop_description_ar;
        }

        return $merchant->shop_description ?? $merchant->shop_details;
    }

    // =========================================================================
    // COLLECTION HELPERS
    // =========================================================================

    /**
     * Format a collection of merchants for list display
     */
    public function formatCollection(Collection $merchants): array
    {
        return $merchants->map(fn($m) => $this->forList($m))->toArray();
    }

    /**
     * Format a collection of merchants as cards
     */
    public function formatCardsCollection(Collection $merchants): array
    {
        return MerchantCardDTO::fromCollection($merchants);
    }
}
