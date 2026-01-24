<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CalloutNote;
use App\Models\CalloutUserLookupKey;

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

    /**
     * Notes for this callout
     */
    public function notes(): HasMany
    {
        return $this->hasMany(CalloutNote::class, 'callout_id');
    }

    /**
     * User lookup keys for this callout
     */
    public function userKeys(): HasMany
    {
        return $this->hasMany(CalloutUserLookupKey::class, 'callout_id');
    }
}
