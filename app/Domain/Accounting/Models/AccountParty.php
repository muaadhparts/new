<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\User;
use App\Models\Courier;

/**
 * AccountParty Model - Accounting parties
 *
 * Domain: Accounting
 * Table: account_parties
 *
 * Represents any party in the accounting system:
 * - platform: The platform (single party)
 * - merchant: Merchants (from users table)
 * - courier: Couriers (from couriers table)
 * - shipping_provider: Shipping companies
 * - payment_provider: Payment companies
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

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function outgoingTransactions(): HasMany
    {
        return $this->hasMany(AccountingLedger::class, 'from_party_id');
    }

    public function incomingTransactions(): HasMany
    {
        return $this->hasMany(AccountingLedger::class, 'to_party_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(AccountBalance::class, 'party_id');
    }

    public function counterpartyBalances(): HasMany
    {
        return $this->hasMany(AccountBalance::class, 'counterparty_id');
    }

    // =========================================================================
    // FACTORY METHODS
    // =========================================================================

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

    // =========================================================================
    // SCOPES
    // =========================================================================

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

    // =========================================================================
    // HELPERS
    // =========================================================================

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
