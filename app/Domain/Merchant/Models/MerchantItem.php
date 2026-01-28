<?php

namespace App\Domain\Merchant\Models;

use App\Domain\Merchant\Traits\HasMerchantItemRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * MerchantItem Model - Merchant's inventory items
 *
 * Domain: Merchant
 * Table: merchant_items
 *
 * This is a CLEAN model with NO business logic.
 * All queries → MerchantItemQuery
 * All pricing → MerchantItemPricingService
 * All stock → MerchantItemStockService
 * All display → MerchantItemDisplayService
 * All CRUD → MerchantItemService
 *
 * @property int $id
 * @property int $catalog_item_id
 * @property int $user_id
 * @property int|null $merchant_branch_id
 * @property int|null $quality_brand_id
 * @property string $item_type
 * @property string|null $affiliate_link
 * @property float $price
 * @property float|null $previous_price
 * @property int $stock
 * @property int|null $whole_sell_qty
 * @property float|null $whole_sell_discount
 * @property bool $preordered
 * @property int|null $minimum_qty
 * @property int $stock_check
 * @property int $status
 * @property string|null $ship
 * @property int $item_condition
 * @property string|null $details
 * @property string|null $policy
 */
class MerchantItem extends Model
{
    use HasFactory;
    use HasMerchantItemRelations;

    protected $table = 'merchant_items';

    protected $fillable = [
        'catalog_item_id',
        'user_id',
        'merchant_branch_id',
        'quality_brand_id',
        'item_type',
        'affiliate_link',
        'price',
        'previous_price',
        'stock',
        'whole_sell_qty',
        'whole_sell_discount',
        'preordered',
        'minimum_qty',
        'stock_check',
        'status',
        'ship',
        'item_condition',
        'details',
        'policy',
    ];

    protected $casts = [
        'catalog_item_id' => 'integer',
        'user_id' => 'integer',
        'merchant_branch_id' => 'integer',
        'quality_brand_id' => 'integer',
        'price' => 'decimal:2',
        'previous_price' => 'decimal:2',
        'stock' => 'integer',
        'whole_sell_qty' => 'integer',
        'whole_sell_discount' => 'decimal:2',
        'preordered' => 'boolean',
        'minimum_qty' => 'integer',
        'stock_check' => 'integer',
        'status' => 'integer',
        'item_condition' => 'integer',
    ];

    /**
     * Check if this item is owned by a specific merchant
     */
    public function isOwnedBy(int $merchantId): bool
    {
        return $this->user_id === $merchantId;
    }
}
