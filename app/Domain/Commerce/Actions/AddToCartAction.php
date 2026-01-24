<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Services\Cart\MerchantCartManager;
use App\Domain\Commerce\DTOs\CartItemDTO;

/**
 * AddToCartAction - Add item to cart
 *
 * Single-responsibility action for adding items to cart.
 * Handles validation, stock checking, and cart update.
 */
class AddToCartAction
{
    public function __construct(
        private MerchantCartManager $cartManager
    ) {}

    /**
     * Execute the action
     *
     * @param int $merchantItemId The merchant item to add
     * @param int $qty Quantity to add
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(int $merchantItemId, int $qty = 1): array
    {
        // Validate quantity
        if ($qty < 1) {
            return [
                'success' => false,
                'message' => __('Quantity must be at least 1'),
            ];
        }

        // Delegate to cart manager
        return $this->cartManager->addItem($merchantItemId, $qty);
    }

    /**
     * Execute with DTO result
     *
     * @param int $merchantItemId
     * @param int $qty
     * @return CartItemDTO|null
     */
    public function executeWithDto(int $merchantItemId, int $qty = 1): ?CartItemDTO
    {
        $result = $this->execute($merchantItemId, $qty);

        if (!$result['success']) {
            return null;
        }

        // Get the item from cart
        $items = $result['data']['items'] ?? [];
        foreach ($items as $item) {
            if (($item['merchant_item_id'] ?? 0) === $merchantItemId) {
                return CartItemDTO::fromArray($item);
            }
        }

        return null;
    }
}
