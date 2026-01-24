<?php

namespace App\Domain\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseTimeline Model - Order timeline/tracking events
 *
 * Domain: Commerce
 * Table: purchase_timelines
 *
 * @property int $id
 * @property int $purchase_id
 * @property string|null $name
 * @property string|null $text
 */
class PurchaseTimeline extends Model
{
    protected $table = 'purchase_timelines';

    protected $fillable = ['purchase_id', 'name', 'text'];

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id')->withDefault();
    }
}
