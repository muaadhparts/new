<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Callout Model - Part callouts on illustrations
 *
 * Domain: Catalog
 * Table: callouts
 *
 * @property int $id
 * @property int $illustration_id
 */
class Callout extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * The illustration this callout belongs to
     */
    public function illustration(): BelongsTo
    {
        return $this->belongsTo(Illustration::class, 'illustration_id');
    }
}
