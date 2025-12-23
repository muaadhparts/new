<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
// Import MerchantProduct model to expose vendor listings via a relationship
use App\Models\MerchantProduct;

class User extends Authenticatable implements JWTSubject
{

    protected $fillable = ['name', 'photo', 'zip', 'city_id', 'state_id', 'country', 'address', 'latitude', 'longitude', 'phone', 'fax', 'email', 'password', 'affilate_code', 'verification_link', 'shop_name', 'owner_name', 'shop_number', 'shop_address', 'reg_number', 'shop_message', 'is_vendor', 'shop_details', 'shop_image', 'shipping_cost', 'date', 'mail_sent', 'email_verified', 'email_token', 'reward', 'warehouse_city', 'warehouse_state', 'warehouse_address', 'warehouse_lat', 'warehouse_lng', 'current_balance'];

    protected $hidden = [
        'password', 'remember_token'
    ];

    public function IsVendor()
    {
        if ($this->is_vendor == 2) {
            return true;
        }
        return false;
    }

    public function orders()
    {
        return $this->hasMany('App\Models\Order');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\Comment');
    }

    public function replies()
    {
        return $this->hasMany('App\Models\Reply');
    }

    public function ratings()
    {
        return $this->hasMany('App\Models\Rating');
    }

    public function wishlists()
    {
        return $this->hasMany('App\Models\Wishlist');
    }

    public function socialProviders()
    {
        return $this->hasMany('App\Models\SocialProvider');
    }

    public function withdraws()
    {
        return $this->hasMany('App\Models\Withdraw');
    }

    public function conversations()
    {
        return $this->hasMany('App\Models\AdminUserConversation');
    }

    public function notifications()
    {
        return $this->hasMany('App\Models\Notification');
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
     * Note: products table no longer has user_id column
     * Use merchantProducts() or vendorProducts() instead
     *
     * @deprecated Use merchantProducts() for direct merchant listings
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        // Return empty collection to prevent errors
        // This method is kept for backward compatibility only
        return $this->hasMany('App\Models\Product')->whereRaw('1 = 0');
    }

    /**
     * Get all unique products that this vendor sells via merchantProducts
     * This returns Product models, not MerchantProduct models
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function vendorProducts()
    {
        return $this->hasManyThrough(
            'App\Models\Product',
            'App\Models\MerchantProduct',
            'user_id',        // Foreign key on merchant_products table
            'id',             // Foreign key on products table
            'id',             // Local key on users table
            'product_id'      // Local key on merchant_products table
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
        return $this->hasMany('App\Models\UserNotification', 'user_id');
    }

    public function subscribes()
    {
        return $this->hasMany('App\Models\UserSubscription');
    }

    public function favorites()
    {
        return $this->hasMany('App\Models\FavoriteSeller');
    }

    public function vendororders()
    {
        return $this->hasMany('App\Models\VendorOrder', 'user_id');
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
     * Get the state associated with the user's address
     */
    public function state()
    {
        return $this->belongsTo('App\Models\State', 'state_id');
    }

    /**
     * Get the country associated with the user's address
     * Note: country field stores country_name, not ID
     */
    public function countryModel()
    {
        return $this->belongsTo('App\Models\Country', 'country', 'country_name');
    }

    public function wishlistCount()
    {
        // Count wishlist items where the underlying product has at least one active merchant listing.
        return \App\Models\Wishlist::where('user_id', '=', $this->id)
            ->whereHas('product', function ($productQuery) {
                // Only include products that have at least one merchant_product entry with status = 1
                $productQuery->whereIn('id', function($subQuery) {
                    $subQuery->select('product_id')
                        ->from('merchant_products')
                        ->where('status', '=', 1);
                });
            })
            ->count();
    }

    /**
     * Get all merchant product entries belonging to this user (vendor).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function merchantProducts()
    {
        return $this->hasMany(MerchantProduct::class, 'user_id');
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
