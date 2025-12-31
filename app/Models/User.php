<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
// Import MerchantItem model to expose vendor listings via a relationship
use App\Models\MerchantItem;
use App\Models\CatalogItem;
use App\Models\SupportThread;
use App\Models\Favorite;

class User extends Authenticatable implements JWTSubject
{

    protected $fillable = ['name', 'photo', 'zip', 'city_id', 'country', 'address', 'latitude', 'longitude', 'phone', 'fax', 'email', 'password', 'affilate_code', 'verification_link', 'shop_name', 'owner_name', 'shop_number', 'shop_address', 'reg_number', 'shop_message', 'is_merchant', 'shop_details', 'shop_image', 'shipping_cost', 'date', 'mail_sent', 'email_verified', 'email_token', 'reward', 'warehouse_city', 'warehouse_address', 'warehouse_lat', 'warehouse_lng', 'current_balance'];

    protected $hidden = [
        'password', 'remember_token'
    ];

    public function IsMerchant()
    {
        if ($this->is_merchant == 2) {
            return true;
        }
        return false;
    }

    /**
     * @deprecated Use IsMerchant() instead
     */
    public function IsVendor()
    {
        return $this->IsMerchant();
    }

    public function purchases()
    {
        return $this->hasMany('App\Models\Purchase');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\Comment');
    }

    public function replies()
    {
        return $this->hasMany('App\Models\Reply');
    }

    public function catalogReviews()
    {
        return $this->hasMany('App\Models\CatalogReview');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function oauthAccounts()
    {
        return $this->hasMany(OauthAccount::class);
    }

    public function withdraws()
    {
        return $this->hasMany('App\Models\Withdraw');
    }

    /**
     * Get all support threads for this user.
     */
    public function supportThreads()
    {
        return $this->hasMany(SupportThread::class);
    }

    /**
     * @deprecated Use supportThreads() instead
     */
    public function conversations()
    {
        return $this->supportThreads();
    }

    public function catalogEvents()
    {
        return $this->hasMany('App\Models\CatalogEvent');
    }

    public function deposits()
    {
        return $this->hasMany('App\Models\Deposit', 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany('App\Models\Transaction', 'user_id')->orderBy('id', 'desc');
    }

    // Multi Vendor

    /**
     * Legacy relationship - DEPRECATED
     * Note: catalog_items table no longer has user_id column
     * Use merchantItems() or catalogItems() instead
     *
     * @deprecated Use merchantItems() for direct merchant listings
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        // Return empty collection to prevent errors
        // This method is kept for backward compatibility only
        return $this->hasMany('App\Models\CatalogItem')->whereRaw('1 = 0');
    }

    /**
     * Get all unique catalog items that this vendor sells via merchantItems
     * This returns CatalogItem models, not MerchantItem models
     *
     * @deprecated Use catalogItems() instead
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function vendorProducts()
    {
        return $this->hasManyThrough(
            'App\Models\CatalogItem',
            'App\Models\MerchantItem',
            'user_id',            // Foreign key on merchant_items table
            'id',                 // Foreign key on catalog_items table
            'id',                 // Local key on users table
            'catalog_item_id'     // Local key on merchant_items table
        );
    }

    public function services()
    {
        return $this->hasMany('App\Models\Service');
    }

    public function senders()
    {
        return $this->hasMany('App\Models\Conversation', 'sent_user');
    }

    public function recievers()
    {
        return $this->hasMany('App\Models\Conversation', 'recieved_user');
    }

    public function notivications()
    {
        return $this->hasMany('App\Models\UserCatalogEvent', 'user_id');
    }

    public function subscribes()
    {
        return $this->hasMany('App\Models\UserSubscription');
    }

    public function favoriteSellers()
    {
        return $this->hasMany('App\Models\FavoriteSeller');
    }

    public function merchantPurchases()
    {
        return $this->hasMany('App\Models\MerchantPurchase', 'user_id');
    }

    public function shippings()
    {
        return $this->hasMany('App\Models\Shipping', 'user_id');
    }

    public function packages()
    {
        return $this->hasMany('App\Models\Package', 'user_id');
    }

    public function reports()
    {
        return $this->hasMany('App\Models\Report', 'user_id');
    }

    public function verifies()
    {
        return $this->hasMany('App\Models\Verification', 'user_id');
    }

    public function sociallinks()
    {
        return $this->hasMany('App\Models\SocialLink', 'user_id');
    }

    // ============================================================================
    // ADDRESS RELATIONSHIPS
    // ============================================================================
    // These relationships allow accessing city, state, and country details
    // from the user's saved address
    // ============================================================================

    /**
     * Get the city associated with the user's address
     */
    public function city()
    {
        return $this->belongsTo('App\Models\City', 'city_id');
    }

    /**
     * Get the country associated with the user's address
     * Note: country field stores country_name, not ID
     */
    public function countryModel()
    {
        return $this->belongsTo('App\Models\Country', 'country', 'country_name');
    }

    public function favoriteCount()
    {
        return Favorite::where('user_id', '=', $this->id)
            ->whereHas('catalogItem', function ($catalogItemQuery) {
                $catalogItemQuery->whereIn('id', function($subQuery) {
                    $subQuery->select('catalog_item_id')
                        ->from('merchant_items')
                        ->where('status', '=', 1);
                });
            })
            ->count();
    }

    /**
     * Get all merchant item entries belonging to this user (vendor).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function merchantItems()
    {
        return $this->hasMany(MerchantItem::class, 'user_id');
    }

    /**
     * @deprecated Use merchantItems() instead
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function merchantProducts()
    {
        return $this->merchantItems();
    }

    /**
     * Get all unique catalog items that this vendor sells via merchantItems
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function catalogItems()
    {
        return $this->hasManyThrough(
            CatalogItem::class,
            MerchantItem::class,
            'user_id',            // Foreign key on merchant_items table
            'id',                 // Foreign key on catalog_items table
            'id',                 // Local key on users table
            'catalog_item_id'     // Local key on merchant_items table
        );
    }

    public function checkVerification()
    {
        return count($this->verifies) > 0 ?
            (empty($this->verifies()->where('admin_warning', '=', '0')->latest('id')->first()->status) ? false : ($this->verifies()->latest('id')->first()->status == 'Pending' ? true : false)) : false;
    }

    public function checkStatus()
    {
        return count($this->verifies) > 0 ? ($this->verifies()->latest('id')->first()->status == 'Verified' ? true : false) : false;
    }

    public function checkWarning()
    {
        return count($this->verifies) > 0 ? (empty($this->verifies()->where('admin_warning', '=', '1')->latest('id')->first()) ? false : (empty($this->verifies()->where('admin_warning', '=', '1')->latest('id')->first()->status) ? true : false)) : false;
    }

    public function displayWarning()
    {
        return $this->verifies()->where('admin_warning', '=', '1')->latest('id')->first()->warning_reason;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
