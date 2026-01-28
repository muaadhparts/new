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

    // =========================================================================
    // EARNINGS & FINANCIAL FORMATTING
    // =========================================================================

    /**
     * Format earnings summary from accounting report
     * Used by IncomeController::index()
     */
    public function formatEarningsSummary(array $report, string $currencySign): array
    {
        return [
            // Sales Summary
            'total_sales' => $currencySign . number_format($report['total_sales'], 2),
            'total_orders' => $report['total_orders'],
            'total_qty' => $report['total_qty'],

            // Platform Deductions
            'total_commission' => $currencySign . number_format($report['total_commission'], 2),
            'total_tax' => $currencySign . number_format($report['total_tax'], 2),
            'total_platform_shipping_fee' => $currencySign . number_format($report['total_platform_shipping_fee'], 2),

            // Shipping Costs
            'total_shipping_cost' => $currencySign . number_format($report['total_shipping_cost'], 2),
            'total_courier_fee' => $currencySign . number_format($report['total_courier_fee'], 2),

            // Net Amount
            'total_net' => $currencySign . number_format($report['total_net'], 2),

            // Settlement Balances (raw values for conditionals + formatted)
            'platform_owes_merchant' => $report['platform_owes_merchant'],
            'merchant_owes_platform' => $report['merchant_owes_platform'],
            'net_balance' => $report['net_balance'],
            'net_balance_formatted' => $currencySign . number_format(abs($report['net_balance']), 2),
            'platform_owes_merchant_formatted' => $currencySign . number_format($report['platform_owes_merchant'], 2),
            'merchant_owes_platform_formatted' => $currencySign . number_format($report['merchant_owes_platform'], 2),

            // Payment Method Breakdown
            'platform_payments' => $report['platform_payments'],
            'merchant_payments' => $report['merchant_payments'],
            'platform_payments_total_formatted' => $currencySign . number_format($report['platform_payments']['total'], 2),
            'merchant_payments_total_formatted' => $currencySign . number_format($report['merchant_payments']['total'], 2),

            // Shipping Breakdown
            'platform_shipping' => $report['platform_shipping'],
            'merchant_shipping' => $report['merchant_shipping'],
            'platform_shipping_cost_formatted' => $currencySign . number_format($report['platform_shipping']['cost'], 2),
            'merchant_shipping_cost_formatted' => $currencySign . number_format($report['merchant_shipping']['cost'], 2),
            'courier_deliveries' => $report['courier_deliveries'],
        ];
    }

    /**
     * Format purchases for earnings table
     */
    public function formatPurchasesForEarnings(Collection $purchases, string $currencySign): Collection
    {
        return $purchases->map(function ($purchase) use ($currencySign) {
            return [
                'id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'payment_owner_id' => $purchase->payment_owner_id,
                'shipping_owner_id' => $purchase->shipping_owner_id,
                'shipping_type' => $purchase->shipping_type,
                'platform_owes_merchant' => $purchase->platform_owes_merchant,
                'merchant_owes_platform' => $purchase->merchant_owes_platform,
                'date_formatted' => $purchase->created_at?->format('d-m-Y') ?? 'N/A',
                'price_formatted' => $currencySign . number_format($purchase->price, 2),
                'commission_amount_formatted' => $currencySign . number_format($purchase->commission_amount, 2),
                'tax_amount_formatted' => $currencySign . number_format($purchase->tax_amount, 2),
                'net_amount_formatted' => $currencySign . number_format($purchase->net_amount, 2),
                'platform_owes_merchant_formatted' => $currencySign . number_format($purchase->platform_owes_merchant, 2),
                'merchant_owes_platform_formatted' => $currencySign . number_format($purchase->merchant_owes_platform, 2),
            ];
        });
    }

    /**
     * Format statement entries for ledger view
     */
    public function formatStatementEntries(array $statement): array
    {
        return collect($statement)->map(function ($entry) {
            $entry['date_formatted'] = isset($entry['date']) ? $entry['date']->format('d-m-Y') : 'N/A';
            return $entry;
        })->toArray();
    }

    /**
     * Format tax report data
     */
    public function formatTaxReport(array $report, Collection $purchases, string $currencySign): array
    {
        return [
            'total_tax' => $currencySign . number_format($report['total_tax'], 2),
            'total_sales' => $currencySign . number_format($report['total_sales'], 2),
            'tax_from_platform_payments' => $currencySign . number_format($report['tax_from_platform_payments'], 2),
            'tax_from_merchant_payments' => $currencySign . number_format($report['tax_from_merchant_payments'], 2),
            'purchases' => $purchases->map(function ($purchase) use ($currencySign) {
                return [
                    'id' => $purchase->id,
                    'purchase_number' => $purchase->purchase_number,
                    'price' => $purchase->price,
                    'tax_amount' => $purchase->tax_amount,
                    'payment_owner_id' => $purchase->payment_owner_id,
                    'price_formatted' => $currencySign . number_format($purchase->price, 2),
                    'tax_amount_formatted' => $currencySign . number_format($purchase->tax_amount, 2),
                    'date_formatted' => $purchase->created_at?->format('d-m-Y') ?? 'N/A',
                ];
            }),
        ];
    }

    /**
     * Format statement totals
     */
    public function formatStatementTotals(array $statement, string $currencySign): array
    {
        return [
            'total_credit' => $currencySign . number_format($statement['total_credit'], 2),
            'total_debit' => $currencySign . number_format($statement['total_debit'], 2),
            'opening_balance' => $statement['opening_balance'],
            'closing_balance' => $statement['closing_balance'],
        ];
    }

    /**
     * Format monthly ledger summary
     */
    public function formatMonthlyLedgerSummary(array $report, string $currencySign): array
    {
        return [
            'total_sales' => $currencySign . number_format($report['total_sales'], 2),
            'total_commission' => $currencySign . number_format($report['total_commission'], 2),
            'total_tax' => $currencySign . number_format($report['total_tax'], 2),
            'total_net' => $currencySign . number_format($report['total_net'], 2),
            'total_orders' => $report['total_orders'],
        ];
    }

    /**
     * Format payouts summary
     */
    public function formatPayoutsSummary(float $pendingAmount, float $totalReceived, string $currencySign): array
    {
        return [
            'pending_amount' => $currencySign . number_format($pendingAmount, 2),
            'total_received' => $currencySign . number_format($totalReceived, 2),
            'pending_raw' => $pendingAmount,
            'total_received_raw' => $totalReceived,
        ];
    }

    /**
     * Format payouts/settlements data
     */
    public function formatPayouts($payouts, string $currencySign): Collection
    {
        return $payouts->map(function ($payout) {
            return [
                'id' => $payout->id,
                'batch_number' => $payout->batch_number,
                'status' => $payout->status,
                'total_amount' => $payout->total_amount,
                'date_formatted' => $payout->settlement_date
                    ? $payout->settlement_date->format('d-m-Y')
                    : ($payout->created_at ? $payout->created_at->format('d-m-Y') : 'N/A'),
                'amount_formatted' => $payout->getFormattedAmount(),
                'status_color' => $payout->getStatusColor(),
                'status_name_ar' => $payout->getStatusNameAr(),
            ];
        });
    }

    // =========================================================================
    // BRANCH DISPLAY FORMATTING
    // =========================================================================

    /**
     * Format branches for list display
     */
    public function formatBranchesForList(Collection $branches): Collection
    {
        return $branches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'item' => $branch,
                'countryName' => $branch->country?->country_name ?? '-',
                'name' => $branch->city?->name ?? '-',
                'location' => $branch->location,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'latitude_formatted' => $branch->latitude ? number_format($branch->latitude, 6) : null,
                'longitude_formatted' => $branch->longitude ? number_format($branch->longitude, 6) : null,
                'status' => $branch->status,
                'statusClass' => $branch->status == 1 ? 'active' : 'deactive',
                'statusActiveSelected' => $branch->status == 1 ? 'selected' : '',
                'statusInactiveSelected' => $branch->status == 0 ? 'selected' : '',
                'statusActiveUrl' => route('merchant-branch-status', ['id' => $branch->id, 'status' => 1]),
                'statusInactiveUrl' => route('merchant-branch-status', ['id' => $branch->id, 'status' => 0]),
                'editUrl' => route('merchant-branch-edit', $branch->id),
                'deleteUrl' => route('merchant-branch-delete', $branch->id),
            ];
        });
    }
}
