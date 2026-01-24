<?php

namespace App\Domain\Identity\Observers;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Str;

/**
 * User Observer
 *
 * Handles User model lifecycle events.
 */
class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        // Generate referral code
        if (empty($user->referral_code)) {
            $user->referral_code = $this->generateReferralCode();
        }

        // Set default role
        if (empty($user->role)) {
            $user->role = 'user';
        }

        // Set default status
        if (!isset($user->status)) {
            $user->status = 1;
        }

        // Generate slug for merchants
        if ($user->role === 'merchant' && empty($user->slug)) {
            $user->slug = $this->generateSlug($user->shop_name ?? $user->name);
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Create default settings for merchants
        if ($user->role === 'merchant') {
            $user->merchantSetting()->create([
                'auto_accept_orders' => false,
                'low_stock_threshold' => 5,
            ]);
        }

        // Dispatch registration event
        event(new \App\Domain\Identity\Events\UserRegisteredEvent($user));
    }

    /**
     * Handle the User "updating" event.
     */
    public function updating(User $user): void
    {
        // Update slug if shop name changed
        if ($user->isDirty('shop_name') && $user->role === 'merchant') {
            $user->slug = $this->generateSlug($user->shop_name);
        }

        // Track email changes
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
    }

    /**
     * Generate unique referral code
     */
    protected function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Generate unique slug
     */
    protected function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (User::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }
}
