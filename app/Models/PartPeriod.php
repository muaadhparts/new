<?php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartPeriod extends Model
{
    protected $table;

    protected $fillable = [
        'part_id',
        'begin_date',
        'end_date',
    ];

    public $timestamps = false;

    public function __construct(array $attributes = [], string $catalogCode = null)
    {
        parent::__construct($attributes);

        // ✨ تحديد الجدول حسب كود الكاتالوج
        if ($catalogCode) {
            $this->setTable('part_periods_' . strtolower($catalogCode));
        }
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'part_id');
    }

    public function extensions()
    {
        return $this->hasMany(PartExtension::class, 'part_period_id');
    }
}
