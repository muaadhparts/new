<?php

namespace App\Domain\Platform\ViewComposers;

use Illuminate\View\View;
use App\Domain\Platform\Services\GlobalData\GlobalDataService;

/**
 * Global Data Composer
 *
 * Provides global platform data to all views.
 */
class GlobalDataComposer
{
    public function __construct(
        protected GlobalDataService $globalDataService
    ) {}

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $view->with('globalData', $this->globalDataService->getData());
    }
}
