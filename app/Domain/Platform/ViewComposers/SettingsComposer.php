<?php

namespace App\Domain\Platform\ViewComposers;

use Illuminate\View\View;
use App\Domain\Platform\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Settings Composer
 *
 * Provides platform settings to views.
 */
class SettingsComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $settings = Cache::remember('platform_settings', 3600, function () {
            return PlatformSetting::all()
                ->groupBy('group')
                ->map(fn ($items) => $items->pluck('value', 'key'));
        });

        $view->with('platformSettings', $settings);
    }
}
