<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Services\Cart\MerchantCartManager;
use App\Domain\Commerce\Services\MerchantCheckout\MerchantPurchaseCreator;
use App\Domain\Commerce\Models\Purchase;
use Illuminate\Support\Facades\DB;

/**
 * ConfirmCheckoutAction - Confirm checkout and create purchase
 *
 * Single-responsibility action for finalizing checkout.
 * Handles stock deduction, purchase creation, and cart cleanup.
 */
class ConfirmCheckoutAction
{
    public function __construct(
        private MerchantCartManager $cartManager,
        private MerchantPurchaseCreator $purchaseCreator
    ) {}

    /**
     * Execute the action
     *
     * @param int $branchId Branch ID
     * @param array $checkoutData Checkout session data
     * @return array{success: bool, message: string, purchase?: Purchase}
     */
    public function execute(int $branchId, array $checkoutData): array
    {
        // Validate branch has items
        if (!$this->cartManager->hasBranchItems($branchId)) {
            return [
                'success' => false,
                'message' => __('Cart is empty'),
            ];
        }

        try {
            return DB::transaction(function () use ($branchId, $checkoutData) {
                // Confirm checkout (deduct stock, release reservations)
                if (!$this->cartManager->confirmBranchCheckout($branchId)) {
                    throw new \RuntimeException(__('Failed to confirm checkout'));
                }

                // Create purchase record
                $purchase = $this->purchaseCreator->create($checkoutData);

                return [
                    'success' => true,
                    'message' => __('Order placed successfully'),
                    'purchase' => $purchase,
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
