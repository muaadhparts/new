<?php

namespace App\Domain\Platform\ViewComposers;

use Illuminate\View\View;
use App\Domain\Platform\Services\MonetaryUnitService;

/**
 * Monetary Unit Composer
 *
 * Provides currency data to views.
 */
class MonetaryUnitComposer
{
    public function __construct(
        protected MonetaryUnitService $monetaryUnitService
    ) {}

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $view->with([
            'currentCurrency' => $this->monetaryUnitService->getCurrent(),
            'availableCurrencies' => $this->monetaryUnitService->getAvailable(),
        ]);
    }
}
