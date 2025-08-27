<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartPeriod extends Model
{
    // يتم تحديد الجدول ديناميكيًا عبر setCatalog()
    protected $table;

    protected $fillable = [
        'part_id',
        'begin_date',
        'end_date',
    ];

    public $timestamps = false;

    /**
     * اضبط جدول الفترات حسب كود الكتالوج (ديناميكي).
     */
    public function setCatalog(string $catalogCode): void
    {
        // dd('PartPeriod@setCatalog', $catalogCode); // لفحص سريع عند الحاجة
        $catalogCode = trim($catalogCode);
        if ($catalogCode !== '') {
            $this->setTable('part_periods_' . strtolower($catalogCode));
        }
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'part_id');
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(PartExtension::class, 'part_period_id');
    }
}
