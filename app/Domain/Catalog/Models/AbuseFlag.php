<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Abuse Flag Model
 *
 * Reports of abuse/issues with catalog items.
 */
class AbuseFlag extends Model
{
    protected $table = 'abuse_flags';

    protected $fillable = [
        'catalog_item_id',
        'merchant_item_id',
        'user_id',
        'name',
        'note',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault(function ($data) {
            foreach ($data->getFillable() as $dt) {
                $data[$dt] = __('Deleted');
            }
        });
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id')->withDefault(function ($data) {
            foreach ($data->getFillable() as $dt) {
                $data[$dt] = __('Deleted');
            }
        });
    }

    public function merchantItem(): BelongsTo
    {
        return $this->belongsTo(MerchantItem::class, 'merchant_item_id')->withDefault();
    }
}
