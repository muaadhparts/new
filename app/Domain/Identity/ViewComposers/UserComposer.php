<?php

namespace App\Domain\Identity\ViewComposers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * User Composer
 *
 * Provides authenticated user data to views.
 */
class UserComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $user = Auth::user();

        if (!$user) {
            $view->with([
                'isAuthenticated' => false,
                'isMerchant' => false,
                'currentUser' => null,
            ]);
            return;
        }

        $view->with([
            'isAuthenticated' => true,
            'isMerchant' => (bool) $user->is_merchant,
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->photo ?? null,
                'isVerified' => !is_null($user->email_verified_at),
            ],
        ]);
    }
}
