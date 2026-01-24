<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PlatformRevenueLog Model - Platform revenue tracking
 *
 * Domain: Accounting
 * Table: platform_revenue_log
 *
 * Tracks all platform revenue from various sources.
 * Used for financial reporting and auditing.
 */
class PlatformRevenueLog extends Model
{
    protected $table = 'platform_revenue_log';

    protected $fillable = [
        'date',
        'source',
        'reference_type',
        'reference_id',
        'amount',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    // === Source Constants ===
    const SOURCE_COMMISSION = 'commission';
    const SOURCE_TAX = 'tax';
    const SOURCE_SHIPPING_MARKUP = 'shipping_markup';
    const SOURCE_COURIER_FEE = 'courier_fee';
    const SOURCE_OTHER = 'other';

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // =========================================================================
    // AGGREGATIONS
    // =========================================================================

    public static function getTotalBySource(string $source, $startDate = null, $endDate = null): float
    {
        $query = self::bySource($source);

        if ($startDate && $endDate) {
            $query->inPeriod($startDate, $endDate);
        }

        return $query->sum('amount');
    }

    public static function getDailyTotals($startDate, $endDate): array
    {
        return self::selectRaw('date, source, SUM(amount) as total')
            ->inPeriod($startDate, $endDate)
            ->groupBy('date', 'source')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->toArray();
    }

    // =========================================================================
    // LABELS
    // =========================================================================

    public function getSourceLabel(): string
    {
        $labels = [
            self::SOURCE_COMMISSION => __('Merchant Commission'),
            self::SOURCE_TAX => __('Tax Collected'),
            self::SOURCE_SHIPPING_MARKUP => __('Shipping Markup'),
            self::SOURCE_COURIER_FEE => __('Courier Fee'),
            self::SOURCE_OTHER => __('Other'),
        ];

        return $labels[$this->source] ?? $this->source;
    }
}
