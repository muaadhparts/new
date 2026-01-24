<?php

namespace App\Domain\Merchant\Traits;

use App\Domain\Identity\Models\User;

/**
 * Belongs To Merchant Trait
 *
 * Provides merchant ownership functionality.
 */
trait BelongsToMerchant
{
    /**
     * Boot the trait
     */
    public static function bootBelongsToMerchant(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getMerchantIdColumn()})) {
                $model->{$model->getMerchantIdColumn()} = $model->resolveDefaultMerchantId();
            }
        });
    }

    /**
     * Get the merchant relationship
     */
    public function merchant()
    {
        return $this->belongsTo(User::class, $this->getMerchantIdColumn());
    }

    /**
     * Get the merchant ID column name
     */
    public function getMerchantIdColumn(): string
    {
        return $this->merchantIdColumn ?? 'user_id';
    }

    /**
     * Resolve default merchant ID
     */
    protected function resolveDefaultMerchantId(): ?int
    {
        if (auth()->check() && auth()->user()->is_merchant) {
            return auth()->id();
        }

        return null;
    }

    /**
     * Scope to merchant
     */
    public function scopeForMerchant($query, int $merchantId)
    {
        return $query->where($this->getMerchantIdColumn(), $merchantId);
    }

    /**
     * Scope to current merchant
     */
    public function scopeForCurrentMerchant($query)
    {
        if (auth()->check()) {
            return $query->forMerchant(auth()->id());
        }

        return $query->whereRaw('1 = 0'); // No results
    }

    /**
     * Check if belongs to merchant
     */
    public function belongsToMerchant(int $merchantId): bool
    {
        return $this->{$this->getMerchantIdColumn()} === $merchantId;
    }

    /**
     * Check if belongs to current merchant
     */
    public function belongsToCurrentMerchant(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return $this->belongsToMerchant(auth()->id());
    }

    /**
     * Authorize for merchant
     */
    public function authorizeForMerchant(?int $merchantId = null): bool
    {
        $merchantId = $merchantId ?? auth()->id();

        if (!$merchantId) {
            return false;
        }

        return $this->belongsToMerchant($merchantId);
    }
}
