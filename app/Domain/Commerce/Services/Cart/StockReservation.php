<?php

namespace App\Domain\Commerce\Services\Cart;

use App\Models\MerchantItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * StockReservation - Manages temporary stock reservations for cart items
 *
 * This ensures that when a customer adds items to cart, the stock is
 * temporarily held to prevent overselling during checkout.
 */
class StockReservation
{
    /**
     * Cache prefix for reservations
     */
    private const CACHE_PREFIX = 'stock_reservation:';

    /**
     * Default reservation duration in minutes
     */
    private const DEFAULT_DURATION = 30;

    /**
     * Session ID for this user's reservations
     */
    private string $sessionId;

    public function __construct()
    {
        $this->sessionId = session()->getId();
    }

    /**
     * Reserve stock for a cart item
     *
     * @param int $merchantItemId
     * @param int $qty Quantity to reserve
     * @param int $minutes Duration of reservation
     * @return bool True if reservation successful
     */
    public function reserve(int $merchantItemId, int $qty, int $minutes = self::DEFAULT_DURATION): bool
    {
        $key = $this->getReservationKey($merchantItemId);

        // Get current available stock
        $available = $this->getAvailableStock($merchantItemId);

        if ($available < $qty) {
            return false;
        }

        // Store reservation
        $reservation = [
            'session_id' => $this->sessionId,
            'merchant_item_id' => $merchantItemId,
            'qty' => $qty,
            'reserved_at' => now()->toDateTimeString(),
            'expires_at' => now()->addMinutes($minutes)->toDateTimeString(),
        ];

        Cache::put($key, $reservation, now()->addMinutes($minutes));

        // Track all reservations for this session
        $this->addToSessionReservations($key);

        return true;
    }

    /**
     * Release a stock reservation
     *
     * @param int $merchantItemId
     * @return bool
     */
    public function release(int $merchantItemId): bool
    {
        $key = $this->getReservationKey($merchantItemId);

        if (Cache::has($key)) {
            Cache::forget($key);
            $this->removeFromSessionReservations($key);
            return true;
        }

        return false;
    }

    /**
     * Update reservation quantity
     *
     * @param int $merchantItemId
     * @param int $newQty
     * @param int $minutes
     * @return bool
     */
    public function update(int $merchantItemId, int $newQty, int $minutes = self::DEFAULT_DURATION): bool
    {
        $key = $this->getReservationKey($merchantItemId);
        $existing = Cache::get($key);

        if (!$existing || $existing['session_id'] !== $this->sessionId) {
            // No existing reservation, create new
            return $this->reserve($merchantItemId, $newQty, $minutes);
        }

        $oldQty = (int) $existing['qty'];
        $diff = $newQty - $oldQty;

        if ($diff > 0) {
            // Need more stock
            $available = $this->getAvailableStock($merchantItemId);
            if ($available < $diff) {
                return false;
            }
        }

        // Update reservation
        $existing['qty'] = $newQty;
        $existing['reserved_at'] = now()->toDateTimeString();
        $existing['expires_at'] = now()->addMinutes($minutes)->toDateTimeString();

        Cache::put($key, $existing, now()->addMinutes($minutes));

        return true;
    }

    /**
     * Release all reservations for current session
     */
    public function releaseAll(): void
    {
        $sessionKey = $this->getSessionReservationsKey();
        $reservations = Cache::get($sessionKey, []);

        foreach ($reservations as $key) {
            Cache::forget($key);
        }

        Cache::forget($sessionKey);
    }

    /**
     * Get available stock (actual stock minus reservations)
     *
     * @param int $merchantItemId
     * @return int
     */
    public function getAvailableStock(int $merchantItemId): int
    {
        $merchantItem = MerchantItem::find($merchantItemId);

        if (!$merchantItem) {
            return 0;
        }

        // Get actual stock
        $actualStock = $this->getEffectiveStock($merchantItem);

        // Subtract all active reservations (except our own)
        $totalReserved = $this->getTotalReserved($merchantItemId);

        return max(0, $actualStock - $totalReserved);
    }

    /**
     * Get total reserved quantity for an item (excluding current session)
     *
     * @param int $merchantItemId
     * @return int
     */
    public function getTotalReserved(int $merchantItemId): int
    {
        // Get all reservations for this item
        // Note: This is a simplified implementation. In production, you'd want
        // to use Redis with SCAN or store reservations in database
        $key = $this->getReservationKey($merchantItemId);
        $ourReservation = Cache::get($key);
        $ourQty = 0;

        if ($ourReservation && $ourReservation['session_id'] === $this->sessionId) {
            $ourQty = (int) $ourReservation['qty'];
        }

        // For simplicity, we'll track total reserved in a separate key
        $totalKey = self::CACHE_PREFIX . "total:{$merchantItemId}";
        $total = (int) Cache::get($totalKey, 0);

        return max(0, $total - $ourQty);
    }

    /**
     * Get reservation for current session
     *
     * @param int $merchantItemId
     * @return array|null
     */
    public function getReservation(int $merchantItemId): ?array
    {
        $key = $this->getReservationKey($merchantItemId);
        $reservation = Cache::get($key);

        if ($reservation && $reservation['session_id'] === $this->sessionId) {
            return $reservation;
        }

        return null;
    }

    /**
     * Check if we have a reservation
     *
     * @param int $merchantItemId
     * @return bool
     */
    public function hasReservation(int $merchantItemId): bool
    {
        return $this->getReservation($merchantItemId) !== null;
    }

    /**
     * Extend reservation duration
     *
     * @param int $merchantItemId
     * @param int $minutes
     * @return bool
     */
    public function extend(int $merchantItemId, int $minutes = self::DEFAULT_DURATION): bool
    {
        $key = $this->getReservationKey($merchantItemId);
        $existing = Cache::get($key);

        if (!$existing || $existing['session_id'] !== $this->sessionId) {
            return false;
        }

        $existing['expires_at'] = now()->addMinutes($minutes)->toDateTimeString();
        Cache::put($key, $existing, now()->addMinutes($minutes));

        return true;
    }

    /**
     * Confirm reservations (convert to actual purchase - reduces real stock)
     * Called after successful checkout
     *
     * @param array $items Array of ['merchant_item_id' => qty, ...]
     * @return bool
     */
    public function confirm(array $items): bool
    {
        DB::beginTransaction();

        try {
            foreach ($items as $merchantItemId => $data) {
                $qty = is_array($data) ? ($data['qty'] ?? 0) : $data;

                // Reduce actual stock
                $merchantItem = MerchantItem::lockForUpdate()->find($merchantItemId);

                if (!$merchantItem) {
                    throw new \Exception("MerchantItem #{$merchantItemId} not found");
                }

                // Update general stock
                $merchantItem->stock = max(0, (int) $merchantItem->stock - $qty);
                $merchantItem->save();

                // Release the reservation
                $this->release($merchantItemId);
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Stock confirmation failed: ' . $e->getMessage());
            return false;
        }
    }

    // ===================== Private Helpers =====================

    /**
     * Get effective stock for a merchant item
     */
    private function getEffectiveStock(MerchantItem $mp): int
    {
        return (int) ($mp->stock ?? 0);
    }

    /**
     * Generate cache key for a reservation
     */
    private function getReservationKey(int $merchantItemId): string
    {
        return self::CACHE_PREFIX . "{$this->sessionId}:{$merchantItemId}";
    }

    /**
     * Get key for tracking all session reservations
     */
    private function getSessionReservationsKey(): string
    {
        return self::CACHE_PREFIX . "session:{$this->sessionId}";
    }

    /**
     * Add reservation key to session tracking
     */
    private function addToSessionReservations(string $key): void
    {
        $sessionKey = $this->getSessionReservationsKey();
        $reservations = Cache::get($sessionKey, []);

        if (!in_array($key, $reservations)) {
            $reservations[] = $key;
            Cache::put($sessionKey, $reservations, now()->addHours(24));
        }
    }

    /**
     * Remove reservation key from session tracking
     */
    private function removeFromSessionReservations(string $key): void
    {
        $sessionKey = $this->getSessionReservationsKey();
        $reservations = Cache::get($sessionKey, []);

        $reservations = array_filter($reservations, fn($k) => $k !== $key);
        Cache::put($sessionKey, array_values($reservations), now()->addHours(24));
    }
}
