<?php

namespace App\Domain\Commerce\ViewComposers;

use Illuminate\View\View;
use App\Domain\Commerce\Services\CartService;

/**
 * Cart Composer
 *
 * Provides cart data to views.
 */
class CartComposer
{
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $view->with([
            'cart' => $this->cartService->getCart(),
            'cartCount' => $this->cartService->getItemCount(),
            'cartTotal' => $this->cartService->getTotal(),
        ]);
    }
}
