<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Services\Cart\MerchantCartManager;

/**
 * RemoveFromCartAction - Remove item from cart
 *
 * Single-responsibility action for removing items from cart.
 */
class RemoveFromCartAction
{
    public function __construct(
        private MerchantCartManager $cartManager
    ) {}

    /**
     * Execute the action
     *
     * @param int $branchId Branch ID
     * @param string $cartKey Cart item key
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(int $branchId, string $cartKey): array
    {
        return $this->cartManager->removeBranchItem($branchId, $cartKey);
    }

    /**
     * Clear entire branch cart
     *
     * @param int $branchId Branch ID
     * @return void
     */
    public function clearBranch(int $branchId): void
    {
        $this->cartManager->clearBranch($branchId);
    }

    /**
     * Clear all cart
     *
     * @return void
     */
    public function clearAll(): void
    {
        $this->cartManager->clearAll();
    }
}
