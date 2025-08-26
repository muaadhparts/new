<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specification extends Model
{
    protected $fillable = ['name', 'label', 'type'];

    /**
     * 🔗 العناصر (القيم) التابعة لهذه المواصفة
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SpecificationItem::class, 'specification_id');
    }
}
