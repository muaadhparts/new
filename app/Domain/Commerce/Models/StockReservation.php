<?php

namespace App\Domain\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\MerchantItem;
use App\Models\User;

/**
 * StockReservation Model - Temporary cart stock reservations
 *
 * Domain: Commerce
 * Table: stock_reservations
 *
 * @property int $id
 * @property string $session_id
 * @property int|null $user_id
 * @property int $merchant_item_id
 * @property int $qty
 * @property string $cart_key
 * @property Carbon $reserved_at
 * @property Carbon $expires_at
 */
class StockReservation extends Model
{
    protected $table = 'stock_reservations';

    protected $fillable = [
        'session_id',
        'user_id',
        'merchant_item_id',
        'qty',
        'cart_key',
        'reserved_at',
        'expires_at',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Reservation duration in minutes (default: 30)
     */
    public static function reservationMinutes(): int
    {
        return (int) config('cart.reservation_minutes', 30);
    }

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public function merchantItem(): BelongsTo
    {
        return $this->belongsTo(MerchantItem::class, 'merchant_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>=', now());
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    // =========================================================================
    // STATIC METHODS
    // =========================================================================

    public static function reserve(
        int $merchantItemId,
        int $qty,
        string $cartKey,
        ?int $userId = null
    ): self {
        $sessionId = session()->getId();
        $expiresAt = now()->addMinutes(self::reservationMinutes());

        return self::updateOrCreate(
            [
                'session_id' => $sessionId,
                'merchant_item_id' => $merchantItemId,
                'cart_key' => $cartKey,
            ],
            [
                'user_id' => $userId ?? auth()->id(),
                'qty' => $qty,
                'reserved_at' => now(),
                'expires_at' => $expiresAt,
            ]
        );
    }

    public static function updateReservation(string $cartKey, int $newQty, ?int $merchantItemId = null): bool
    {
        $sessionId = session()->getId();

        $reservation = self::where('session_id', $sessionId)
            ->where('cart_key', $cartKey)
            ->first();

        if ($reservation) {
            $reservation->update([
                'qty' => $newQty,
                'expires_at' => now()->addMinutes(self::reservationMinutes()),
            ]);
            return true;
        }

        if ($merchantItemId) {
            self::create([
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
                'merchant_item_id' => $merchantItemId,
                'qty' => $newQty,
                'cart_key' => $cartKey,
                'reserved_at' => now(),
                'expires_at' => now()->addMinutes(self::reservationMinutes()),
            ]);
            return true;
        }

        return false;
    }

    public static function release(string $cartKey, bool $skipStockReturn = false): bool
    {
        $sessionId = session()->getId();

        $reservation = self::where('session_id', $sessionId)
            ->where('cart_key', $cartKey)
            ->first();

        if ($reservation) {
            if (!$skipStockReturn) {
                self::returnStockToCatalogItem($reservation);
            }
            $reservation->delete();
            return true;
        }

        return false;
    }

    public static function releaseAll(?string $sessionId = null): int
    {
        $sessionId = $sessionId ?? session()->getId();

        $reservations = self::where('session_id', $sessionId)->get();
        $count = 0;

        foreach ($reservations as $reservation) {
            self::returnStockToCatalogItem($reservation);
            $reservation->delete();
            $count++;
        }

        return $count;
    }

    public static function clearAfterPurchase(?string $sessionId = null): int
    {
        $sessionId = $sessionId ?? session()->getId();
        return self::where('session_id', $sessionId)->delete();
    }

    public static function releaseExpired(): int
    {
        $expired = self::expired()->get();
        $count = 0;

        foreach ($expired as $reservation) {
            self::returnStockToCatalogItem($reservation);
            $reservation->delete();
            $count++;
        }

        return $count;
    }

    protected static function returnStockToCatalogItem(self $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            DB::table('merchant_items')
                ->where('id', $reservation->merchant_item_id)
                ->increment('stock', $reservation->qty, ['updated_at' => now()]);
        });
    }

    // =========================================================================
    // INSTANCE METHODS
    // =========================================================================

    public function extend(): void
    {
        $this->update([
            'expires_at' => now()->addMinutes(self::reservationMinutes()),
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function remainingSeconds(): int
    {
        if ($this->isExpired()) return 0;
        return now()->diffInSeconds($this->expires_at, false);
    }

    public function remainingMinutes(): int
    {
        return (int) ceil($this->remainingSeconds() / 60);
    }
}
