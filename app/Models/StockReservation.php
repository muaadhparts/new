<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Stock Reservation Model
 * =======================
 * يدير حجز المخزون المؤقت للسلة
 *
 * @property int $id
 * @property string $session_id
 * @property int|null $user_id
 * @property int $merchant_product_id
 * @property string|null $size
 * @property int $qty
 * @property string $cart_key
 * @property Carbon $reserved_at
 * @property Carbon $expires_at
 */
class StockReservation extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'merchant_product_id',
        'size',
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
     * مدة الحجز بالدقائق (افتراضي: 30 دقيقة)
     */
    public static function reservationMinutes(): int
    {
        return (int) config('cart.reservation_minutes', 30);
    }

    /**
     * العلاقة مع MerchantProduct
     */
    public function merchantProduct()
    {
        return $this->belongsTo(MerchantProduct::class);
    }

    /**
     * العلاقة مع User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الحجوزات المنتهية
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * الحجوزات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>=', now());
    }

    /**
     * حجوزات session معين
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * إنشاء أو تحديث حجز
     */
    public static function reserve(
        int $merchantProductId,
        int $qty,
        string $cartKey,
        ?string $size = null,
        ?int $userId = null
    ): self {
        $sessionId = session()->getId();
        $expiresAt = now()->addMinutes(self::reservationMinutes());

        return self::updateOrCreate(
            [
                'session_id' => $sessionId,
                'merchant_product_id' => $merchantProductId,
                'cart_key' => $cartKey,
            ],
            [
                'user_id' => $userId ?? auth()->id(),
                'size' => $size,
                'qty' => $qty,
                'reserved_at' => now(),
                'expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * تحديث كمية الحجز (أو إنشاءه إذا لم يكن موجوداً)
     */
    public static function updateReservation(string $cartKey, int $newQty, ?int $merchantProductId = null, ?string $size = null): bool
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

        // إنشاء حجز جديد إذا لم يكن موجوداً وتم تمرير merchant_product_id
        if ($merchantProductId) {
            self::create([
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
                'merchant_product_id' => $merchantProductId,
                'size' => $size,
                'qty' => $newQty,
                'cart_key' => $cartKey,
                'reserved_at' => now(),
                'expires_at' => now()->addMinutes(self::reservationMinutes()),
            ]);
            return true;
        }

        return false;
    }

    /**
     * إلغاء حجز
     * @param bool $skipStockReturn إذا true، لا يتم إرجاع المخزون (يُستخدم عندما يتم إرجاع المخزون من مكان آخر)
     */
    public static function release(string $cartKey, bool $skipStockReturn = false): bool
    {
        $sessionId = session()->getId();

        $reservation = self::where('session_id', $sessionId)
            ->where('cart_key', $cartKey)
            ->first();

        if ($reservation) {
            // إرجاع المخزون فقط إذا لم يُطلب تخطيه
            if (!$skipStockReturn) {
                self::returnStockToProduct($reservation);
            }
            $reservation->delete();
            return true;
        }

        return false;
    }

    /**
     * إلغاء جميع حجوزات الـ session
     */
    public static function releaseAll(?string $sessionId = null): int
    {
        $sessionId = $sessionId ?? session()->getId();

        $reservations = self::where('session_id', $sessionId)->get();
        $count = 0;

        foreach ($reservations as $reservation) {
            self::returnStockToProduct($reservation);
            $reservation->delete();
            $count++;
        }

        return $count;
    }

    /**
     * حذف الحجوزات بعد إتمام الشراء (بدون إرجاع المخزون)
     * يُستخدم عند نجاح الدفع - المخزون تم بيعه فعلياً
     */
    public static function clearAfterPurchase(?string $sessionId = null): int
    {
        $sessionId = $sessionId ?? session()->getId();

        // حذف مباشر بدون إرجاع المخزون
        return self::where('session_id', $sessionId)->delete();
    }

    /**
     * تحرير الحجوزات المنتهية وإرجاع المخزون
     */
    public static function releaseExpired(): int
    {
        $expired = self::expired()->get();
        $count = 0;

        foreach ($expired as $reservation) {
            self::returnStockToProduct($reservation);
            $reservation->delete();
            $count++;
        }

        return $count;
    }

    /**
     * إرجاع المخزون للمنتج
     */
    protected static function returnStockToProduct(self $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $mp = DB::table('merchant_products')
                ->where('id', $reservation->merchant_product_id)
                ->lockForUpdate()
                ->first();

            if (!$mp) return;

            // إذا كان له size
            if ($reservation->size && !empty($mp->size) && !empty($mp->size_qty)) {
                $sizes = array_map('trim', explode(',', $mp->size));
                $qtys = array_map('trim', explode(',', $mp->size_qty));
                $idx = array_search(trim($reservation->size), $sizes, true);

                if ($idx !== false && isset($qtys[$idx])) {
                    $qtys[$idx] = (int)$qtys[$idx] + $reservation->qty;
                    DB::table('merchant_products')
                        ->where('id', $reservation->merchant_product_id)
                        ->update([
                            'size_qty' => implode(',', $qtys),
                            'updated_at' => now(),
                        ]);
                    return;
                }
            }

            // إرجاع للـ stock العام
            DB::table('merchant_products')
                ->where('id', $reservation->merchant_product_id)
                ->increment('stock', $reservation->qty, ['updated_at' => now()]);
        });
    }

    /**
     * تمديد الحجز (تحديث expires_at)
     */
    public function extend(): void
    {
        $this->update([
            'expires_at' => now()->addMinutes(self::reservationMinutes()),
        ]);
    }

    /**
     * هل الحجز منتهي؟
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * الوقت المتبقي بالثواني
     */
    public function remainingSeconds(): int
    {
        if ($this->isExpired()) return 0;
        return now()->diffInSeconds($this->expires_at, false);
    }

    /**
     * الوقت المتبقي بالدقائق
     */
    public function remainingMinutes(): int
    {
        return (int) ceil($this->remainingSeconds() / 60);
    }
}
