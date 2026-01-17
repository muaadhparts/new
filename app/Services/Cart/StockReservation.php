<?php

namespace App\Services\Cart;

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
     * @param string|null $size Size variant (affects stock per size)
     * @param int $minutes Duration of reservation
     * @return bool True if reservation successful
     */
    public function reserve(int $merchantItemId, int $qty, ?string $size = null, int $minutes = self::DEFAULT_DURATION): bool
    {
        $key = $this->getReservationKey($merchantItemId, $size);

        // Get current available stock
        $available = $this->getAvailableStock($merchantItemId, $size);

        if ($available < $qty) {
            return false;
        }

        // Store reservation
        $reservation = [
            'session_id' => $this->sessionId,
            'merchant_item_id' => $merchantItemId,
            'size' => $size,
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
     * @param string|null $size
     * @return bool
     */
    public function release(int $merchantItemId, ?string $size = null): bool
    {
        $key = $this->getReservationKey($merchantItemId, $size);

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
     * @param string|null $size
     * @param int $minutes
     * @return bool
     */
    public function update(int $merchantItemId, int $newQty, ?string $size = null, int $minutes = self::DEFAULT_DURATION): bool
    {
        $key = $this->getReservationKey($merchantItemId, $size);
        $existing = Cache::get($key);

        if (!$existing || $existing['session_id'] !== $this->sessionId) {
            // No existing reservation, create new
            return $this->reserve($merchantItemId, $newQty, $size, $minutes);
        }

        $oldQty = (int) $existing['qty'];
        $diff = $newQty - $oldQty;

        if ($diff > 0) {
            // Need more stock
            $available = $this->getAvailableStock($merchantItemId, $size);
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
     * @param string|null $size
     * @return int
     */
    public function getAvailableStock(int $merchantItemId, ?string $size = null): int
    {
        $merchantItem = MerchantItem::find($merchantItemId);

        if (!$merchantItem) {
            return 0;
        }

        // Get actual stock
        $actualStock = $this->getEffectiveStock($merchantItem, $size);

        // Subtract all active reservations (except our own)
        $totalReserved = $this->getTotalReserved($merchantItemId, $size);

        return max(0, $actualStock - $totalReserved);
    }

    /**
     * Get total reserved quantity for an item (excluding current session)
     *
     * @param int $merchantItemId
     * @param string|null $size
     * @return int
     */
    public function getTotalReserved(int $merchantItemId, ?string $size = null): int
    {
        $pattern = self::CACHE_PREFIX . "{$merchantItemId}:*";

        // Get all reservations for this item
        // Note: This is a simplified implementation. In production, you'd want
        // to use Redis with SCAN or store reservations in database
        $key = $this->getReservationKey($merchantItemId, $size);
        $ourReservation = Cache::get($key);
        $ourQty = 0;

        if ($ourReservation && $ourReservation['session_id'] === $this->sessionId) {
            $ourQty = (int) $ourReservation['qty'];
        }

        // For simplicity, we'll track total reserved in a separate key
        $totalKey = self::CACHE_PREFIX . "total:{$merchantItemId}:" . ($size ?? '_');
        $total = (int) Cache::get($totalKey, 0);

        return max(0, $total - $ourQty);
    }

    /**
     * Get reservation for current session
     *
     * @param int $merchantItemId
     * @param string|null $size
     * @return array|null
     */
    public function getReservation(int $merchantItemId, ?string $size = null): ?array
    {
        $key = $this->getReservationKey($merchantItemId, $size);
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
     * @param string|null $size
     * @return bool
     */
    public function hasReservation(int $merchantItemId, ?string $size = null): bool
    {
        return $this->getReservation($merchantItemId, $size) !== null;
    }

    /**
     * Extend reservation duration
     *
     * @param int $merchantItemId
     * @param string|null $size
     * @param int $minutes
     * @return bool
     */
    public function extend(int $merchantItemId, ?string $size = null, int $minutes = self::DEFAULT_DURATION): bool
    {
        $key = $this->getReservationKey($merchantItemId, $size);
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
                $size = is_array($data) ? ($data['size'] ?? null) : null;

                // Reduce actual stock
                $merchantItem = MerchantItem::lockForUpdate()->find($merchantItemId);

                if (!$merchantItem) {
                    throw new \Exception("MerchantItem #{$merchantItemId} not found");
                }

                // Update stock based on size or general stock
                if ($size && !empty($merchantItem->size) && !empty($merchantItem->size_qty)) {
                    // Update size-specific stock
                    $sizes = is_array($merchantItem->size) ? $merchantItem->size : explode(',', $merchantItem->size);
                    $qtys = is_array($merchantItem->size_qty) ? $merchantItem->size_qty : explode(',', $merchantItem->size_qty);

                    $idx = array_search(trim($size), array_map('trim', $sizes), true);
                    if ($idx !== false && isset($qtys[$idx])) {
                        $qtys[$idx] = max(0, (int) $qtys[$idx] - $qty);
                        $merchantItem->size_qty = implode(',', $qtys);
                        $merchantItem->save();
                    }
                } else {
                    // Update general stock
                    $merchantItem->stock = max(0, (int) $merchantItem->stock - $qty);
                    $merchantItem->save();
                }

                // Release the reservation
                $this->release($merchantItemId, $size);
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
    private function getEffectiveStock(MerchantItem $mp, ?string $size): int
    {
        if ($size && !empty($mp->size) && !empty($mp->size_qty)) {
            $sizes = is_array($mp->size) ? $mp->size : explode(',', $mp->size);
            $qtys = is_array($mp->size_qty) ? $mp->size_qty : explode(',', $mp->size_qty);
            $idx = array_search(trim($size), array_map('trim', $sizes), true);

            if ($idx !== false && isset($qtys[$idx])) {
                return (int) $qtys[$idx];
            }
        }

        return (int) ($mp->stock ?? 0);
    }

    /**
     * Generate cache key for a reservation
     */
    private function getReservationKey(int $merchantItemId, ?string $size): string
    {
        $sizeKey = $size ? md5($size) : '_';
        return self::CACHE_PREFIX . "{$this->sessionId}:{$merchantItemId}:{$sizeKey}";
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
