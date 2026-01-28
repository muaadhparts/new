<?php

namespace App\Domain\Platform\Services;

use Carbon\Carbon;

/**
 * OperatorDisplayService - Centralized formatting for operator/admin display
 *
 * API-Ready: Common formatting patterns for dashboard, reports, and admin UI.
 * DATA FLOW POLICY: Controller → Service → DTO → View/API
 *
 * @see docs/rules/DATA_FLOW_POLICY.md
 */
class OperatorDisplayService
{
    // =========================================================================
    // STATS CARD FORMATTING
    // =========================================================================

    /**
     * Format a stat for dashboard card display
     */
    public function formatStat(
        string $label,
        $value,
        ?string $subtitle = null,
        ?string $icon = null,
        ?string $trend = null,
        ?float $trendValue = null
    ): array {
        return [
            'label' => __($label),
            'value' => is_numeric($value) ? number_format($value) : $value,
            'subtitle' => $subtitle ? __($subtitle) : null,
            'icon' => $icon,
            'trend' => $trend, // 'up', 'down', 'stable'
            'trend_value' => $trendValue !== null ? abs($trendValue) . '%' : null,
            'trend_class' => match ($trend) {
                'up' => 'text-success',
                'down' => 'text-danger',
                default => 'text-muted',
            },
            'trend_icon' => match ($trend) {
                'up' => 'fa-arrow-up',
                'down' => 'fa-arrow-down',
                default => 'fa-minus',
            },
        ];
    }

    /**
     * Format a monetary stat for dashboard card
     */
    public function formatMoneyStat(
        string $label,
        float $amount,
        ?string $subtitle = null,
        ?string $icon = null,
        ?string $trend = null,
        ?float $trendValue = null
    ): array {
        return [
            'label' => __($label),
            'value' => monetaryUnit()->format($amount),
            'raw_value' => $amount,
            'subtitle' => $subtitle ? __($subtitle) : null,
            'icon' => $icon,
            'trend' => $trend,
            'trend_value' => $trendValue !== null ? abs($trendValue) . '%' : null,
            'trend_class' => match ($trend) {
                'up' => 'text-success',
                'down' => 'text-danger',
                default => 'text-muted',
            },
            'trend_icon' => match ($trend) {
                'up' => 'fa-arrow-up',
                'down' => 'fa-arrow-down',
                default => 'fa-minus',
            },
        ];
    }

    // =========================================================================
    // DATE RANGE FORMATTING
    // =========================================================================

    /**
     * Parse and format date range from request
     */
    public function parseDateRange(
        ?string $startDate = null,
        ?string $endDate = null,
        int $defaultDays = 30
    ): array {
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays($defaultDays)->startOfDay();

        return [
            'start' => $start,
            'end' => $end,
            'start_formatted' => $start->format('Y-m-d'),
            'end_formatted' => $end->format('Y-m-d'),
            'start_display' => $start->format('d M Y'),
            'end_display' => $end->format('d M Y'),
            'range_label' => $start->format('d M') . ' - ' . $end->format('d M Y'),
            'days_count' => $start->diffInDays($end) + 1,
        ];
    }

    /**
     * Get common date range presets
     */
    public function getDateRangePresets(): array
    {
        return [
            'today' => [
                'label' => __('Today'),
                'start' => now()->startOfDay()->format('Y-m-d'),
                'end' => now()->endOfDay()->format('Y-m-d'),
            ],
            'yesterday' => [
                'label' => __('Yesterday'),
                'start' => now()->subDay()->startOfDay()->format('Y-m-d'),
                'end' => now()->subDay()->endOfDay()->format('Y-m-d'),
            ],
            'last_7_days' => [
                'label' => __('Last 7 Days'),
                'start' => now()->subDays(6)->startOfDay()->format('Y-m-d'),
                'end' => now()->endOfDay()->format('Y-m-d'),
            ],
            'last_30_days' => [
                'label' => __('Last 30 Days'),
                'start' => now()->subDays(29)->startOfDay()->format('Y-m-d'),
                'end' => now()->endOfDay()->format('Y-m-d'),
            ],
            'this_month' => [
                'label' => __('This Month'),
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ],
            'last_month' => [
                'label' => __('Last Month'),
                'start' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
                'end' => now()->subMonth()->endOfMonth()->format('Y-m-d'),
            ],
            'this_year' => [
                'label' => __('This Year'),
                'start' => now()->startOfYear()->format('Y-m-d'),
                'end' => now()->endOfYear()->format('Y-m-d'),
            ],
        ];
    }

    // =========================================================================
    // REPORT HEADER DATA
    // =========================================================================

    /**
     * Build report header display data
     */
    public function buildReportHeader(
        string $title,
        array $dateRange,
        ?array $filters = null
    ): array {
        return [
            'title' => __($title),
            'date_range' => $dateRange,
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'generated_at_display' => now()->format('d M Y, h:i A'),
            'filters' => $filters,
            'currency' => monetaryUnit()->getCurrent(),
            'currency_sign' => monetaryUnit()->getSymbol(),
        ];
    }

    // =========================================================================
    // TABLE FORMATTING
    // =========================================================================

    /**
     * Format a price for table cell display
     */
    public function formatTablePrice(float $amount, bool $showSign = true): string
    {
        if ($showSign) {
            return monetaryUnit()->format($amount);
        }
        return number_format($amount, 2);
    }

    /**
     * Format a date for table cell display
     */
    public function formatTableDate(?\DateTimeInterface $date): string
    {
        return $date?->format('Y-m-d') ?? '-';
    }

    /**
     * Format a datetime for table cell display
     */
    public function formatTableDateTime(?\DateTimeInterface $date): string
    {
        return $date?->format('Y-m-d H:i') ?? '-';
    }

    /**
     * Format a percentage for table cell display
     */
    public function formatTablePercentage(?float $value): string
    {
        if ($value === null) {
            return '-';
        }
        return number_format($value, 1) . '%';
    }

    // =========================================================================
    // BADGE FORMATTING
    // =========================================================================

    /**
     * Get badge display data for a boolean value
     */
    public function booleanBadge(bool $value, ?string $trueLabel = null, ?string $falseLabel = null): array
    {
        return [
            'value' => $value,
            'label' => $value
                ? __($trueLabel ?? 'Yes')
                : __($falseLabel ?? 'No'),
            'class' => $value ? 'bg-success' : 'bg-secondary',
            'icon' => $value ? 'fa-check' : 'fa-times',
        ];
    }

    /**
     * Get badge for active/inactive status
     */
    public function activeBadge(bool $active): array
    {
        return $this->booleanBadge($active, 'Active', 'Inactive');
    }

    // =========================================================================
    // SUMMARY ROW FORMATTING
    // =========================================================================

    /**
     * Build a summary row for reports
     */
    public function buildSummaryRow(array $totals): array
    {
        $formatted = [];

        foreach ($totals as $key => $value) {
            if (is_numeric($value)) {
                // Check if it's likely a monetary value (has decimals or large number)
                if (strpos((string) $value, '.') !== false || $value > 100) {
                    $formatted[$key] = monetaryUnit()->format($value);
                } else {
                    $formatted[$key] = number_format($value);
                }
                $formatted[$key . '_raw'] = $value;
            } else {
                $formatted[$key] = $value;
            }
        }

        return $formatted;
    }

    // =========================================================================
    // CHART DATA FORMATTING
    // =========================================================================

    /**
     * Format data for Chart.js line/bar chart
     */
    public function formatChartData(array $labels, array $values, ?string $label = null): array
    {
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $label ? __($label) : '',
                    'data' => $values,
                ],
            ],
            // Pre-formatted for JavaScript embedding
            'labels_json' => json_encode($labels),
            'values_json' => json_encode($values),
            'labels_string' => implode(',', array_map(fn($l) => "'{$l}'", $labels)),
            'values_string' => implode(',', $values),
        ];
    }

    // =========================================================================
    // COMPARISON FORMATTING
    // =========================================================================

    /**
     * Calculate and format comparison between two values
     */
    public function formatComparison(float $current, float $previous, bool $higherIsBetter = true): array
    {
        $difference = $current - $previous;
        $percentChange = $previous != 0
            ? (($current - $previous) / abs($previous)) * 100
            : ($current != 0 ? 100 : 0);

        $isPositive = $difference > 0;
        $isImprovement = $higherIsBetter ? $isPositive : !$isPositive;

        return [
            'current' => $current,
            'previous' => $previous,
            'current_formatted' => monetaryUnit()->format($current),
            'previous_formatted' => monetaryUnit()->format($previous),
            'difference' => $difference,
            'difference_formatted' => ($difference >= 0 ? '+' : '') . monetaryUnit()->format($difference),
            'percent_change' => round($percentChange, 1),
            'percent_formatted' => ($percentChange >= 0 ? '+' : '') . round($percentChange, 1) . '%',
            'trend' => $isPositive ? 'up' : ($difference < 0 ? 'down' : 'stable'),
            'is_improvement' => $isImprovement,
            'class' => $isImprovement ? 'text-success' : ($difference != 0 ? 'text-danger' : 'text-muted'),
            'icon' => $isPositive ? 'fa-arrow-up' : ($difference < 0 ? 'fa-arrow-down' : 'fa-minus'),
        ];
    }
}
