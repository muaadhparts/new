<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * AccountParty - الأطراف المحاسبية
 *
 * يمثل أي طرف في النظام المحاسبي:
 * - platform: المنصة (طرف واحد)
 * - merchant: التجار (من جدول users)
 * - courier: المناديب (من جدول couriers)
 * - shipping_provider: شركات الشحن (tryoto, aramex, etc)
 * - payment_provider: شركات الدفع (stripe, myfatoorah, etc)
 */
class AccountParty extends Model
{
    protected $table = 'account_parties';

    protected $fillable = [
        'party_type',
        'reference_type',
        'reference_id',
        'name',
        'code',
        'email',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // === Party Type Constants ===
    const TYPE_PLATFORM = 'platform';
    const TYPE_MERCHANT = 'merchant';
    const TYPE_COURIER = 'courier';
    const TYPE_SHIPPING_PROVIDER = 'shipping_provider';
    const TYPE_PAYMENT_PROVIDER = 'payment_provider';

    // ═══════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════

    /**
     * الكيان المصدر (User, Courier, etc)
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * المعاملات الصادرة (من هذا الطرف)
     */
    public function outgoingTransactions(): HasMany
    {
        return $this->hasMany(AccountingLedger::class, 'from_party_id');
    }

    /**
     * المعاملات الواردة (إلى هذا الطرف)
     */
    public function incomingTransactions(): HasMany
    {
        return $this->hasMany(AccountingLedger::class, 'to_party_id');
    }

    /**
     * أرصدة هذا الطرف
     */
    public function balances(): HasMany
    {
        return $this->hasMany(AccountBalance::class, 'party_id');
    }

    /**
     * أرصدة الأطراف الأخرى معه
     */
    public function counterpartyBalances(): HasMany
    {
        return $this->hasMany(AccountBalance::class, 'counterparty_id');
    }

    // ═══════════════════════════════════════════════════════════════
    // FACTORY METHODS - إنشاء الأطراف
    // ═══════════════════════════════════════════════════════════════

    /**
     * الحصول على طرف المنصة (واحد فقط)
     */
    public static function platform(): self
    {
        return static::where('party_type', self::TYPE_PLATFORM)->first()
            ?? static::create([
                'party_type' => self::TYPE_PLATFORM,
                'name' => 'Platform',
                'code' => 'platform',
                'is_active' => true,
            ]);
    }

    /**
     * الحصول على/إنشاء طرف تاجر
     */
    public static function forMerchant(User $user): self
    {
        return static::firstOrCreate(
            [
                'party_type' => self::TYPE_MERCHANT,
                'reference_type' => User::class,
                'reference_id' => $user->id,
            ],
            [
                'name' => $user->shop_name ?? $user->name,
                'code' => 'merchant_' . $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => true,
            ]
        );
    }

    /**
     * الحصول على/إنشاء طرف مندوب
     */
    public static function forCourier(Courier $courier): self
    {
        return static::firstOrCreate(
            [
                'party_type' => self::TYPE_COURIER,
                'reference_type' => Courier::class,
                'reference_id' => $courier->id,
            ],
            [
                'name' => $courier->name,
                'code' => 'courier_' . $courier->id,
                'email' => $courier->email,
                'phone' => $courier->phone,
                'is_active' => (bool) $courier->status,
            ]
        );
    }

    /**
     * الحصول على/إنشاء طرف شركة شحن
     */
    public static function forShippingProvider(string $providerCode, ?string $name = null): self
    {
        $code = 'shipping_' . strtolower($providerCode);

        return static::firstOrCreate(
            [
                'party_type' => self::TYPE_SHIPPING_PROVIDER,
                'code' => $code,
            ],
            [
                'name' => $name ?? ucfirst($providerCode),
                'is_active' => true,
            ]
        );
    }

    /**
     * الحصول على/إنشاء طرف شركة دفع
     */
    public static function forPaymentProvider(string $providerCode, ?string $name = null): self
    {
        $code = 'payment_' . strtolower($providerCode);

        return static::firstOrCreate(
            [
                'party_type' => self::TYPE_PAYMENT_PROVIDER,
                'code' => $code,
            ],
            [
                'name' => $name ?? ucfirst($providerCode),
                'is_active' => true,
            ]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('party_type', $type);
    }

    public function scopeMerchants($query)
    {
        return $query->where('party_type', self::TYPE_MERCHANT);
    }

    public function scopeCouriers($query)
    {
        return $query->where('party_type', self::TYPE_COURIER);
    }

    public function scopeShippingProviders($query)
    {
        return $query->where('party_type', self::TYPE_SHIPPING_PROVIDER);
    }

    public function scopePaymentProviders($query)
    {
        return $query->where('party_type', self::TYPE_PAYMENT_PROVIDER);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    public function isPlatform(): bool
    {
        return $this->party_type === self::TYPE_PLATFORM;
    }

    public function isMerchant(): bool
    {
        return $this->party_type === self::TYPE_MERCHANT;
    }

    public function isCourier(): bool
    {
        return $this->party_type === self::TYPE_COURIER;
    }

    public function isShippingProvider(): bool
    {
        return $this->party_type === self::TYPE_SHIPPING_PROVIDER;
    }

    public function isPaymentProvider(): bool
    {
        return $this->party_type === self::TYPE_PAYMENT_PROVIDER;
    }

    /**
     * الحصول على اسم نوع الطرف بالعربية
     */
    public function getTypeNameAr(): string
    {
        return match ($this->party_type) {
            self::TYPE_PLATFORM => 'المنصة',
            self::TYPE_MERCHANT => 'تاجر',
            self::TYPE_COURIER => 'مندوب',
            self::TYPE_SHIPPING_PROVIDER => 'شركة شحن',
            self::TYPE_PAYMENT_PROVIDER => 'شركة دفع',
            default => 'غير محدد',
        };
    }

    /**
     * الحصول على الأيقونة
     */
    public function getIcon(): string
    {
        return match ($this->party_type) {
            self::TYPE_PLATFORM => 'fas fa-building',
            self::TYPE_MERCHANT => 'fas fa-store',
            self::TYPE_COURIER => 'fas fa-motorcycle',
            self::TYPE_SHIPPING_PROVIDER => 'fas fa-truck',
            self::TYPE_PAYMENT_PROVIDER => 'fas fa-credit-card',
            default => 'fas fa-user',
        };
    }
}
