<?php

namespace App\Http\View\Composers;

use App\Domain\Shipping\Services\CustomerLocationService;
use Illuminate\View\View;

/**
 * LocationViewComposer - Provides location data to views
 *
 * Injects customer location data into views that need it,
 * avoiding direct service calls in blade templates.
 *
 * ARCHITECTURE:
 * - View Composer Pattern
 * - Separation of Concerns
 * - Single Responsibility Principle
 */
class LocationViewComposer
{
    public function __construct(
        private CustomerLocationService $locationService
    ) {}

    /**
     * Bind data to the view.
     *
     * @param View $view
     * @return void
     */
    public function compose(View $view): void
    {
        $view->with([
            'customerHasLocation' => $this->locationService->hasLocation(),
            'customerLocationDisplay' => $this->locationService->getDisplayText(),
        ]);
    }
}
