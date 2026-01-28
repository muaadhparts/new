<?php

namespace App\Domain\Identity\Services;

use App\Domain\Identity\Models\User;
use App\Domain\Identity\DTOs\UserProfileDTO;
use Illuminate\Support\Collection;

/**
 * UserDisplayService - Centralized formatting for user display
 *
 * API-Ready: All formatting in one place for Web and API consumption.
 * DATA FLOW POLICY: Controller → Service → DTO → View/API
 *
 * @see docs/rules/DATA_FLOW_POLICY.md
 */
class UserDisplayService
{
    /**
     * User account status configuration
     */
    private const STATUS_CONFIG = [
        0 => [
            'label_key' => 'Inactive',
            'class' => 'bg-secondary text-white',
            'color' => 'secondary',
            'icon' => 'fa-user-slash',
        ],
        1 => [
            'label_key' => 'Active',
            'class' => 'bg-success text-white',
            'color' => 'success',
            'icon' => 'fa-user-check',
        ],
    ];

    /**
     * Email verification status
     */
    private const VERIFICATION_CONFIG = [
        0 => [
            'label_key' => 'Unverified',
            'class' => 'bg-warning text-dark',
            'color' => 'warning',
            'icon' => 'fa-envelope',
        ],
        1 => [
            'label_key' => 'Verified',
            'class' => 'bg-success text-white',
            'color' => 'success',
            'icon' => 'fa-envelope-circle-check',
        ],
    ];

    // =========================================================================
    // USER NAME FORMATTING
    // =========================================================================

    /**
     * Get user display name
     */
    public function getDisplayName(User|array $user): string
    {
        if (is_array($user)) {
            return trim($user['name'] ?? '') ?: __('Guest');
        }

        return trim($user->name ?? '') ?: __('Guest');
    }

    /**
     * Get user initials for avatar
     */
    public function getInitials(User|array $user): string
    {
        $name = $this->getDisplayName($user);

        if ($name === __('Guest')) {
            return 'G';
        }

        $words = explode(' ', $name);
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
        }

        return $initials ?: 'U';
    }

    // =========================================================================
    // STATUS FORMATTING
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
     * Get full status display data
     */
    public function getStatusDisplay(int $status): array
    {
        $config = self::STATUS_CONFIG[$status] ?? [
            'label_key' => 'Unknown',
            'class' => 'bg-secondary text-white',
            'color' => 'secondary',
            'icon' => 'fa-circle',
        ];

        return [
            'status' => $status,
            'label' => __($config['label_key']),
            'class' => $config['class'],
            'color' => $config['color'],
            'icon' => $config['icon'],
            'is_active' => $status === 1,
        ];
    }

    // =========================================================================
    // VERIFICATION STATUS
    // =========================================================================

    /**
     * Get email verification display data
     */
    public function getVerificationDisplay(User|array $user): array
    {
        $verified = is_array($user)
            ? !empty($user['email_verified_at'])
            : !empty($user->email_verified_at);

        $config = self::VERIFICATION_CONFIG[$verified ? 1 : 0];

        return [
            'is_verified' => $verified,
            'label' => __($config['label_key']),
            'class' => $config['class'],
            'color' => $config['color'],
            'icon' => $config['icon'],
            'verified_at' => $verified
                ? (is_array($user) ? $user['email_verified_at'] : $user->email_verified_at?->format('Y-m-d'))
                : null,
        ];
    }

    // =========================================================================
    // AVATAR/PHOTO
    // =========================================================================

    /**
     * Get user photo URL with fallback
     */
    public function getPhotoUrl(User|array $user): string
    {
        $photo = is_array($user) ? ($user['photo'] ?? null) : ($user->photo ?? null);
        $isProvider = is_array($user) ? ($user['is_provider'] ?? 0) : ($user->is_provider ?? 0);

        if (!$photo) {
            return asset('assets/images/avatar.png');
        }

        // Provider users have full URL or different path
        if ($isProvider == 1) {
            if (filter_var($photo, FILTER_VALIDATE_URL)) {
                return $photo;
            }
            return asset($photo);
        }

        // Regular users store in users folder
        if (filter_var($photo, FILTER_VALIDATE_URL)) {
            return $photo;
        }

        return asset('assets/images/users/' . $photo);
    }

    /**
     * Get avatar display data
     */
    public function getAvatarDisplay(User|array $user): array
    {
        return [
            'url' => $this->getPhotoUrl($user),
            'initials' => $this->getInitials($user),
            'name' => $this->getDisplayName($user),
        ];
    }

    // =========================================================================
    // USER PROFILE DATA
    // =========================================================================

    /**
     * Build display data for user profile
     */
    public function forProfile(User $user): UserProfileDTO
    {
        return UserProfileDTO::fromUser($user);
    }

    /**
     * Build display data for user card/list item
     */
    public function forCard(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $this->getDisplayName($user),
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $this->getAvatarDisplay($user),
            'status' => $this->getStatusDisplay($user->status ?? 1),
            'verification' => $this->getVerificationDisplay($user),
            'member_since' => $user->created_at?->format('M Y') ?? __('N/A'),
            'last_login' => $user->last_login?->diffForHumans() ?? __('Never'),
        ];
    }

    /**
     * Build display data for admin user list
     */
    public function forList(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $this->getDisplayName($user),
            'email' => $user->email,
            'phone' => $user->phone,
            'photo_url' => $this->getPhotoUrl($user),
            'status' => $user->status ?? 1,
            'status_label' => $this->getStatusLabel($user->status ?? 1),
            'status_class' => $this->getStatusClass($user->status ?? 1),
            'is_verified' => !empty($user->email_verified_at),
            'created_at' => $user->created_at?->format('Y-m-d'),
            'last_login' => $user->last_login?->format('Y-m-d H:i'),
            'edit_url' => route('operator.user.edit', $user->id),
        ];
    }

    // =========================================================================
    // USER STATS
    // =========================================================================

    /**
     * Build user stats display data
     */
    public function formatUserStats(array $stats = []): array
    {
        return [
            'total_purchases' => (int) ($stats['total_purchases'] ?? 0),
            'total_spent' => monetaryUnit()->format($stats['total_spent'] ?? 0),
            'total_spent_raw' => (float) ($stats['total_spent'] ?? 0),
            'pending_purchases' => (int) ($stats['pending_purchases'] ?? 0),
            'completed_purchases' => (int) ($stats['completed_purchases'] ?? 0),
            'favorite_items' => (int) ($stats['favorite_items'] ?? 0),
            'wallet_balance' => monetaryUnit()->format($stats['wallet_balance'] ?? 0),
            'wallet_balance_raw' => (float) ($stats['wallet_balance'] ?? 0),
            'referral_earnings' => monetaryUnit()->format($stats['referral_earnings'] ?? 0),
        ];
    }

    // =========================================================================
    // ADDRESS FORMATTING
    // =========================================================================

    /**
     * Format user address for display
     */
    public function formatAddress(User|array $user): string
    {
        $parts = [];

        if (is_array($user)) {
            if (!empty($user['address'])) $parts[] = $user['address'];
            if (!empty($user['city'])) $parts[] = $user['city'];
            if (!empty($user['country'])) $parts[] = $user['country'];
            if (!empty($user['zip'])) $parts[] = $user['zip'];
        } else {
            if (!empty($user->address)) $parts[] = $user->address;
            if (!empty($user->city)) $parts[] = $user->city;
            if (!empty($user->country)) $parts[] = $user->country;
            if (!empty($user->zip)) $parts[] = $user->zip;
        }

        return implode(', ', $parts) ?: __('No address');
    }

    /**
     * Get address as structured data
     */
    public function getAddressDisplay(User|array $user): array
    {
        return [
            'address' => is_array($user) ? ($user['address'] ?? null) : ($user->address ?? null),
            'city' => is_array($user) ? ($user['city'] ?? null) : ($user->city ?? null),
            'country' => is_array($user) ? ($user['country'] ?? null) : ($user->country ?? null),
            'zip' => is_array($user) ? ($user['zip'] ?? null) : ($user->zip ?? null),
            'formatted' => $this->formatAddress($user),
        ];
    }

    // =========================================================================
    // COLLECTION HELPERS
    // =========================================================================

    /**
     * Format a collection of users for list display
     */
    public function formatCollection(Collection $users): array
    {
        return $users->map(fn($u) => $this->forList($u))->toArray();
    }
}
