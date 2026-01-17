<?php

namespace App\Services\MerchantCheckout;

use App\Models\MerchantCart;
use App\Models\User;
use Illuminate\Support\Facades\Session;

/**
 * Cart operations for Merchant Checkout
 *
 * Filter and manage cart items per merchant
 */
class MerchantCartService
{
    /**
     * Get full cart from session
     */
    public function getCart(): ?Cart
    {
        $cart = Session::get('cart');
        return $cart instanceof Cart ? $cart : null;
    }

    /**
     * Get cart items for specific merchant
     */
    public function getMerchantItems(int $merchantId): array
    {
        $cart = $this->getCart();
        if (!$cart || empty($cart->items)) {
            return [];
        }

        $merchantItems = [];
        foreach ($cart->items as $key => $item) {
            $itemMerchantId = $this->extractMerchantId($item);
            if ($itemMerchantId == $merchantId) {
                $merchantItems[$key] = $item;
            }
        }

        return $merchantItems;
    }

    /**
     * Check if merchant has items in cart
     */
    public function hasMerchantItems(int $merchantId): bool
    {
        return !empty($this->getMerchantItems($merchantId));
    }

    /**
     * Get merchants in cart
     */
    public function getMerchantsInCart(): array
    {
        $cart = $this->getCart();
        if (!$cart || empty($cart->items)) {
            return [];
        }

        $merchantIds = [];
        foreach ($cart->items as $item) {
            $merchantId = $this->extractMerchantId($item);
            if ($merchantId && !in_array($merchantId, $merchantIds)) {
                $merchantIds[] = $merchantId;
            }
        }

        return $merchantIds;
    }

    /**
     * Get merchant info with cart summary
     */
    public function getMerchantCartSummary(int $merchantId): array
    {
        $items = $this->getMerchantItems($merchantId);
        $merchant = User::find($merchantId);

        $totalQty = 0;
        $totalPrice = 0;

        foreach ($items as $item) {
            $totalQty += (int)($item['qty'] ?? 1);
            // price in cart is already (unit_price * qty)
            $totalPrice += (float)($item['price'] ?? 0);
        }

        return [
            'merchant_id' => $merchantId,
            'merchant' => $merchant ? [
                'id' => $merchant->id,
                'name' => $merchant->shop_name ?? $merchant->name,
                'name_ar' => $merchant->shop_name_ar ?? $merchant->shop_name ?? $merchant->name,
                'phone' => $merchant->phone,
                'email' => $merchant->email,
                'photo' => $merchant->photo,
            ] : null,
            'items' => $items,
            'items_count' => count($items),
            'total_qty' => $totalQty,
            'total_price' => round($totalPrice, 2),
        ];
    }

    /**
     * Get all merchants with their cart summaries
     */
    public function getAllMerchantsSummary(): array
    {
        $merchantIds = $this->getMerchantsInCart();
        $summaries = [];

        foreach ($merchantIds as $merchantId) {
            $summaries[$merchantId] = $this->getMerchantCartSummary($merchantId);
        }

        return $summaries;
    }

    /**
     * Remove merchant items from cart (after successful purchase)
     */
    public function removeMerchantItems(int $merchantId): void
    {
        $cart = $this->getCart();
        if (!$cart || empty($cart->items)) {
            return;
        }

        $newItems = [];
        $newTotalQty = 0;
        $newTotalPrice = 0;

        foreach ($cart->items as $key => $item) {
            $itemMerchantId = $this->extractMerchantId($item);
            if ($itemMerchantId != $merchantId) {
                $newItems[$key] = $item;
                $newTotalQty += (int)($item['qty'] ?? 1);
                // price in cart is already (unit_price * qty)
                $newTotalPrice += (float)($item['price'] ?? 0);
            }
        }

        $cart->items = $newItems;
        $cart->totalQty = $newTotalQty;
        $cart->totalPrice = $newTotalPrice;

        if (empty($newItems)) {
            Session::forget('cart');
        } else {
            Session::put('cart', $cart);
        }

        Session::save();
    }

    /**
     * Check if cart is empty
     */
    public function isCartEmpty(): bool
    {
        $cart = $this->getCart();
        return !$cart || empty($cart->items);
    }

    /**
     * Check if other merchants have items after removing one
     */
    public function hasOtherMerchants(int $excludeMerchantId): bool
    {
        $merchantIds = $this->getMerchantsInCart();
        $otherMerchants = array_filter($merchantIds, fn($id) => $id != $excludeMerchantId);
        return !empty($otherMerchants);
    }

    /**
     * Build cart payload for purchase storage
     */
    public function buildCartPayload(int $merchantId): array
    {
        $items = $this->getMerchantItems($merchantId);
        $totalQty = 0;
        $totalPrice = 0;

        foreach ($items as $item) {
            $totalQty += (int)($item['qty'] ?? 1);
            // price in cart is already (unit_price * qty)
            $totalPrice += (float)($item['price'] ?? 0);
        }

        return [
            'totalQty' => $totalQty,
            'totalPrice' => round($totalPrice, 2),
            'items' => $items,
        ];
    }

    /**
     * Extract merchant ID from cart item
     */
    protected function extractMerchantId(array $item): ?int
    {
        // Try multiple possible keys
        $merchantId = $item['user_id']
            ?? $item['merchant_id']
            ?? $item['item']['user_id'] ?? null
            ?? $item['item']['merchant_user_id'] ?? null;

        return $merchantId ? (int)$merchantId : null;
    }

    /**
     * Get cart total quantity
     */
    public function getTotalQty(): int
    {
        $cart = $this->getCart();
        return $cart ? (int)$cart->totalQty : 0;
    }

    /**
     * Get cart total price
     */
    public function getTotalPrice(): float
    {
        $cart = $this->getCart();
        return $cart ? (float)$cart->totalPrice : 0;
    }
}
