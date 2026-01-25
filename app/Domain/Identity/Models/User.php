<?php

namespace App\Domain\Identity\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Commerce\Models\SupportThread;
use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Merchant\Models\MerchantCommission;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Models\BuyerNote;
use App\Domain\Commerce\Models\NoteResponse;
use App\Domain\Catalog\Models\CatalogReview;
use App\Domain\Accounting\Models\Withdraw;
use App\Domain\Identity\Models\UserCatalogEvent as CatalogEvent;
use App\Domain\Identity\Models\UserCatalogEvent;
use App\Domain\Commerce\Models\ChatThread;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Shipping\Models\Shipping;
use App\Domain\Catalog\Models\AbuseFlag;
use App\Domain\Merchant\Models\TrustBadge;
use App\Domain\Identity\Models\NetworkPresence;
use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\Country;

/**
 * User Model - Buyers and Merchants
 *
 * Domain: Identity
 * Table: users
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property int $is_merchant (0=buyer, 1=pending, 2=approved merchant)
 * @property string|null $shop_name
 * @property string|null $shop_name_ar
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $fillable = [
        'name', 'photo', 'zip', 'city', 'city_id', 'country', 'address',
        'phone', 'fax', 'email', 'password', 'affilate_code', 'affilate_income',
        'verification_link', 'is_provider', 'status', 'ban', 'balance',
        'shop_name', 'shop_name_ar', 'owner_name', 'shop_number', 'shop_address',
        'reg_number', 'shop_message', 'is_merchant', 'shop_details', 'shop_image',
        'merchant_logo', 'f_url', 'g_url', 't_url', 'l_url',
        'f_check', 'g_check', 't_check', 'l_check',
        'shipping_cost', 'date', 'mail_sent', 'email_verified', 'email_token',
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];

    // =========================================================
    // MERCHANT CHECK
    // =========================================================

    /**
     * Check if user is an approved merchant
     */
    public function IsMerchant(): bool
    {
        return $this->is_merchant == 2;
    }

    // =========================================================
    // RELATIONS
    // =========================================================

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function buyerNotes(): HasMany
    {
        return $this->hasMany(BuyerNote::class);
    }

    public function noteResponses(): HasMany
    {
        return $this->hasMany(NoteResponse::class);
    }

    public function catalogReviews(): HasMany
    {
        return $this->hasMany(CatalogReview::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(FavoriteSeller::class);
    }

    public function oauthAccounts(): HasMany
    {
        return $this->hasMany(OauthAccount::class);
    }

    public function withdraws(): HasMany
    {
        return $this->hasMany(Withdraw::class);
    }

    public function supportThreads(): HasMany
    {
        return $this->hasMany(SupportThread::class);
    }

    public function catalogEvents(): HasMany
    {
        return $this->hasMany(CatalogEvent::class);
    }

    public function merchantCommission(): HasOne
    {
        return $this->hasOne(MerchantCommission::class);
    }

    /**
     * @deprecated Use catalogItems() via hasManyThrough instead
     */
    public function merchantCatalogitem(): HasManyThrough
    {
        return $this->hasManyThrough(
            CatalogItem::class,
            MerchantItem::class,
            'user_id',
            'id',
            'id',
            'catalog_item_id'
        );
    }

    public function senders(): HasMany
    {
        return $this->hasMany(ChatThread::class, 'sent_user');
    }

    public function recievers(): HasMany
    {
        return $this->hasMany(ChatThread::class, 'recieved_user');
    }

    public function userCatalogEvents(): HasMany
    {
        return $this->hasMany(UserCatalogEvent::class, 'user_id');
    }

    /**
     * Alias for catalogEvents() - backwards compatibility
     */
    public function notifications(): HasMany
    {
        return $this->catalogEvents();
    }

    public function favoriteSellers(): HasMany
    {
        return $this->hasMany(FavoriteSeller::class);
    }

    public function merchantPurchases(): HasMany
    {
        return $this->hasMany(MerchantPurchase::class, 'user_id');
    }

    public function shippings(): HasMany
    {
        return $this->hasMany(Shipping::class, 'user_id');
    }

    public function abuseFlags(): HasMany
    {
        return $this->hasMany(AbuseFlag::class, 'user_id');
    }

    public function trustBadges(): HasMany
    {
        return $this->hasMany(TrustBadge::class, 'user_id');
    }

    public function networkPresences(): HasMany
    {
        return $this->hasMany(NetworkPresence::class, 'user_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function countryModel(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country', 'country_name');
    }

    public function merchantItems(): HasMany
    {
        return $this->hasMany(MerchantItem::class, 'user_id');
    }

    public function catalogItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            CatalogItem::class,
            MerchantItem::class,
            'user_id',
            'id',
            'id',
            'catalog_item_id'
        );
    }

    // =========================================================
    // HELPERS
    // =========================================================

    public function favoriteCount(): int
    {
        return FavoriteSeller::where('user_id', '=', $this->id)
            ->whereHas('catalogItem', function ($catalogItemQuery) {
                $catalogItemQuery->whereIn('id', function ($subQuery) {
                    $subQuery->select('catalog_item_id')
                        ->from('merchant_items')
                        ->where('status', '=', 1);
                });
            })
            ->count();
    }

    public function hasPendingTrustBadge(): bool
    {
        return count($this->trustBadges) > 0 ?
            (empty($this->trustBadges()->where('admin_warning', '=', '0')->latest('id')->first()->status) ? false : ($this->trustBadges()->latest('id')->first()->status == 'Pending' ? true : false)) : false;
    }

    public function isTrustBadgeTrusted(): bool
    {
        return count($this->trustBadges) > 0 ? ($this->trustBadges()->latest('id')->first()->status == 'Trusted' ? true : false) : false;
    }

    public function hasTrustBadgeWarning(): bool
    {
        return count($this->trustBadges) > 0 ? (empty($this->trustBadges()->where('admin_warning', '=', '1')->latest('id')->first()) ? false : (empty($this->trustBadges()->where('admin_warning', '=', '1')->latest('id')->first()->status) ? true : false)) : false;
    }

    public function getTrustBadgeWarningReason(): ?string
    {
        return $this->trustBadges()->where('admin_warning', '=', '1')->latest('id')->first()?->warning_reason;
    }

    // =========================================================
    // JWT INTERFACE
    // =========================================================

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
