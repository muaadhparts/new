<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Services\Cart\MerchantCartManager;

/**
 * UpdateCartItemAction - Update cart item quantity
 *
 * Single-responsibility action for updating cart item quantities.
 */
class UpdateCartItemAction
{
    public function __construct(
        private MerchantCartManager $cartManager
    ) {}

    /**
     * Execute the action
     *
     * @param int $branchId Branch ID
     * @param string $cartKey Cart item key
     * @param int $qty New quantity
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(int $branchId, string $cartKey, int $qty): array
    {
        // Validate quantity
        if ($qty < 1) {
            return [
                'success' => false,
                'message' => __('Quantity must be at least 1'),
            ];
        }

        // Delegate to cart manager
        return $this->cartManager->updateBranchQty($branchId, $cartKey, $qty);
    }
}
