<?php

namespace App\Domain\Merchant\ViewComposers;

use Illuminate\View\View;
use App\Domain\Merchant\Models\MerchantBranch;
use Illuminate\Support\Facades\Auth;

/**
 * Branch Composer
 *
 * Provides merchant branches to views.
 */
class BranchComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $merchantId = Auth::id();

        if (!$merchantId) {
            return;
        }

        $branches = MerchantBranch::where('user_id', $merchantId)
            ->where('status', 1)
            ->with('city:id,name,name_ar')
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

        $view->with('merchantBranches', $branches);
    }
}
