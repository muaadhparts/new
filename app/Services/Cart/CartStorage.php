<?php

namespace App\Services\Cart;

use Illuminate\Support\Facades\Session;

/**
 * CartStorage - Session-based cart persistence
 *
 * SINGLE SOURCE OF TRUTH for cart data.
 * No fallbacks, no legacy support.
 */
class CartStorage
{
    public const SESSION_KEY = 'merchant_cart';

    public function get(): array
    {
        return Session::get(self::SESSION_KEY, $this->getEmptyCart());
    }

    public function save(array $cart): void
    {
        Session::put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function hasItems(): bool
    {
        $cart = $this->get();
        return !empty($cart['items']);
    }

    public function getEmptyCart(): array
    {
        return [
            'items' => [],
            'totals' => [
                'qty' => 0,
                'subtotal' => 0.0,
                'discount' => 0.0,
                'total' => 0.0,
            ],
        ];
    }

    public function getItem(string $key): ?array
    {
        $cart = $this->get();
        return $cart['items'][$key] ?? null;
    }

    public function setItem(string $key, array $item): void
    {
        $cart = $this->get();
        $cart['items'][$key] = $item;
        $this->save($cart);
    }

    public function removeItem(string $key): void
    {
        $cart = $this->get();
        unset($cart['items'][$key]);
        $this->save($cart);
    }

    public function getItems(): array
    {
        $cart = $this->get();
        return $cart['items'] ?? [];
    }

    public function getTotals(): array
    {
        $cart = $this->get();
        return $cart['totals'] ?? $this->getEmptyCart()['totals'];
    }

    public function updateTotals(array $totals): void
    {
        $cart = $this->get();
        $cart['totals'] = $totals;
        $this->save($cart);
    }

    public function hasItem(string $key): bool
    {
        $cart = $this->get();
        return isset($cart['items'][$key]);
    }

    public function getItemCount(): int
    {
        $cart = $this->get();
        return count($cart['items'] ?? []);
    }

    public function getTotalQty(): int
    {
        $totals = $this->getTotals();
        return (int) ($totals['qty'] ?? 0);
    }

    public function getTotalPrice(): float
    {
        $totals = $this->getTotals();
        return (float) ($totals['total'] ?? 0);
    }

    public function getItemsByMerchant(): array
    {
        $items = $this->getItems();
        $grouped = [];

        foreach ($items as $key => $item) {
            $merchantId = (int) ($item['merchant_id'] ?? 0);

            if (!isset($grouped[$merchantId])) {
                $grouped[$merchantId] = [
                    'merchant_id' => $merchantId,
                    'merchant_name' => $item['merchant_name'] ?? '',
                    'merchant_name_ar' => $item['merchant_name_ar'] ?? '',
                    'items' => [],
                    'subtotal' => 0.0,
                    'qty' => 0,
                ];
            }

            $grouped[$merchantId]['items'][$key] = $item;
            $grouped[$merchantId]['subtotal'] += (float) ($item['total_price'] ?? 0);
            $grouped[$merchantId]['qty'] += (int) ($item['qty'] ?? 0);
        }

        return $grouped;
    }

    public function getItemsForMerchant(int $merchantId): array
    {
        $items = $this->getItems();
        $merchantItems = [];

        foreach ($items as $key => $item) {
            if ((int) ($item['merchant_id'] ?? 0) === $merchantId) {
                $merchantItems[$key] = $item;
            }
        }

        return $merchantItems;
    }

    public function getMerchantIds(): array
    {
        $items = $this->getItems();
        $ids = [];

        foreach ($items as $item) {
            $merchantId = (int) ($item['merchant_id'] ?? 0);
            if ($merchantId > 0 && !in_array($merchantId, $ids)) {
                $ids[] = $merchantId;
            }
        }

        return $ids;
    }
}
