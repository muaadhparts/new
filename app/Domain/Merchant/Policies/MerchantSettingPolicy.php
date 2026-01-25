<?php

namespace App\Domain\Merchant\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Models\MerchantSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Merchant Setting Policy
 *
 * Determines authorization for merchant settings actions.
 */
class MerchantSettingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if merchant can view settings
     */
    public function view(User $user, MerchantSetting $setting): bool
    {
        return $user->id === $setting->merchant_id;
    }

    /**
     * Determine if merchant can update settings
     */
    public function update(User $user, MerchantSetting $setting): bool
    {
        return $user->id === $setting->merchant_id;
    }

    /**
     * Determine if merchant can update store info
     */
    public function updateStoreInfo(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }

    /**
     * Determine if merchant can update shipping settings
     */
    public function updateShipping(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }

    /**
     * Determine if merchant can update payment settings
     */
    public function updatePayment(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }

    /**
     * Determine if merchant can update notification settings
     */
    public function updateNotifications(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }

    /**
     * Determine if merchant can manage API credentials
     */
    public function manageApi(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }
}
