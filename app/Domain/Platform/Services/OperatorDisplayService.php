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

    // =========================================================================
    // MERCHANT STATEMENT FORMATTING
    // =========================================================================

    /**
     * Format merchant statement for display
     * Used by AccountLedgerController::merchantStatement()
     *
     * MULTI-CHANNEL: Uses monetaryUnit()->format() for centralized formatting
     * Changes here reflect on: Web, Mobile, API, WhatsApp
     */
    public function formatMerchantStatement($statement): array
    {
        $summary = $statement->summary;

        return [
            'merchant_name' => $statement->merchant->name,
            'merchant_code' => $statement->merchant->code,
            'merchant_id' => $statement->merchant->reference_id,
            'period_label' => $statement->getPeriodLabel(),

            // Summary - formatted (CENTRALIZED via monetaryUnit)
            'total_sales_formatted' => monetaryUnit()->format($summary['total_sales']),
            'total_commission_formatted' => monetaryUnit()->format($summary['total_commission']),
            'total_tax_formatted' => monetaryUnit()->format($summary['total_tax']),
            'deductions_formatted' => monetaryUnit()->format($summary['total_commission'] + $summary['total_tax']),
            'net_receivable_formatted' => monetaryUnit()->format($summary['net_receivable']),
            'balance_due_formatted' => monetaryUnit()->format($summary['balance_due']),
            'settlements_received_formatted' => monetaryUnit()->format($summary['settlements_received']),
            'shipping_earned_formatted' => monetaryUnit()->format($summary['shipping_earned']),
            'total_credits_formatted' => monetaryUnit()->format($summary['total_sales'] + $summary['shipping_earned']),
            'total_debits_formatted' => monetaryUnit()->format($summary['total_commission'] + $summary['total_tax'] + $summary['settlements_received']),
            'transaction_count' => $summary['transaction_count'],

            // Balances - formatted
            'opening_balance_formatted' => monetaryUnit()->format($statement->openingBalance),
            'closing_balance_formatted' => monetaryUnit()->format($statement->closingBalance),

            // Raw values for conditionals
            'balance_due' => $summary['balance_due'],

            // Entries (already formatted by getFormattedEntries but need price formatting)
            'entries' => collect($statement->getFormattedEntries())->map(function ($entry) {
                return array_merge($entry, [
                    'debit_formatted' => $entry['debit'] > 0 ? monetaryUnit()->format($entry['debit']) : '-',
                    'credit_formatted' => $entry['credit'] > 0 ? monetaryUnit()->format($entry['credit']) : '-',
                    'balance_formatted' => monetaryUnit()->format($entry['balance']),
                ]);
            })->toArray(),
        ];
    }

    /**
     * Format pending amounts for display
     *
     * MULTI-CHANNEL: Uses monetaryUnit()->format() for centralized formatting
     */
    public function formatPendingAmounts(array $pendingAmounts): array
    {
        return [
            'from_platform' => $pendingAmounts['from_platform'],
            'to_platform' => $pendingAmounts['to_platform'],
            'net_receivable' => $pendingAmounts['net_receivable'],
            'from_platform_formatted' => monetaryUnit()->format($pendingAmounts['from_platform']),
            'to_platform_formatted' => monetaryUnit()->format($pendingAmounts['to_platform']),
            'net_receivable_formatted' => monetaryUnit()->format($pendingAmounts['net_receivable']),
        ];
    }

    // =========================================================================
    // SHIPPING COMPANY STATEMENT FORMATTING
    // =========================================================================

    /**
     * Format shipping company statement for display
     * Used by AccountLedgerController::shippingCompanyStatement()
     *
     * MULTI-CHANNEL: Uses monetaryUnit()->format() for centralized formatting
     */
    public function formatShippingCompanyStatement(array $data): array
    {
        $totalShippingFees = $data['totalShippingFees'];
        $totalCodCollected = $data['totalCodCollected'];
        $owesToPlatform = $data['owesToPlatform'];
        $owesToMerchant = $data['owesToMerchant'];
        $pendingToPlatform = $data['pendingToPlatform'];
        $pendingToMerchant = $data['pendingToMerchant'];
        $settledToPlatform = $data['settledToPlatform'];
        $settledToMerchant = $data['settledToMerchant'];
        $netBalance = $data['netBalance'];

        return [
            // Summary - formatted (CENTRALIZED)
            'totalShippingFees_formatted' => monetaryUnit()->format($totalShippingFees),
            'totalCodCollected_formatted' => monetaryUnit()->format($totalCodCollected),
            'owesToPlatform_formatted' => monetaryUnit()->format($owesToPlatform),
            'owesToMerchant_formatted' => monetaryUnit()->format($owesToMerchant),

            // Pending settlements
            'pendingToPlatform_formatted' => monetaryUnit()->format($pendingToPlatform),
            'pendingToMerchant_formatted' => monetaryUnit()->format($pendingToMerchant),
            'totalPending_formatted' => monetaryUnit()->format($pendingToPlatform + $pendingToMerchant),

            // Settled
            'settledToPlatform_formatted' => monetaryUnit()->format($settledToPlatform),
            'settledToMerchant_formatted' => monetaryUnit()->format($settledToMerchant),
            'totalSettled_formatted' => monetaryUnit()->format($settledToPlatform + $settledToMerchant),

            // Net balance
            'netBalance' => $netBalance,
            'netBalance_formatted' => monetaryUnit()->format(abs($netBalance)),
            'shippingFees_plus_formatted' => '+' . monetaryUnit()->format($totalShippingFees),
            'codCollected_minus_formatted' => '-' . monetaryUnit()->format($totalCodCollected),

            // Raw values for conditionals
            'pendingToPlatform' => $pendingToPlatform,
            'pendingToMerchant' => $pendingToMerchant,
        ];
    }

    /**
     * Format shipping company statement entries
     *
     * MULTI-CHANNEL: Uses monetaryUnit()->format() for centralized formatting
     */
    public function formatShippingCompanyEntries(array $statement): array
    {
        return array_map(function ($entry) {
            return array_merge($entry, [
                'shipping_fee_formatted' => $entry['shipping_fee'] > 0 ? monetaryUnit()->format($entry['shipping_fee']) : '',
                'cod_collected_formatted' => $entry['cod_collected'] > 0 ? monetaryUnit()->format($entry['cod_collected']) : '',
                'owes_platform_formatted' => $entry['owes_platform'] > 0 ? monetaryUnit()->format($entry['owes_platform']) : '',
                'owes_merchant_formatted' => $entry['owes_merchant'] > 0 ? monetaryUnit()->format($entry['owes_merchant']) : '',
                'balance_formatted' => monetaryUnit()->format(abs($entry['balance'])),
            ]);
        }, $statement);
    }

    // =========================================================================
    // RECEIVABLES/PAYABLES REPORT FORMATTING
    // =========================================================================

    /**
     * Format receivables/payables report for display
     * Used by AccountLedgerController::receivablesPayablesReport()
     *
     * MULTI-CHANNEL: Uses monetaryUnit()->format() for centralized formatting
     */
    public function formatReceivablesPayablesReport(array $report): array
    {
        // Helper function to format balance collection (CENTRALIZED)
        $formatBalances = function ($balances) {
            return $balances->map(function ($balance) {
                $balance->pending_amount_formatted = monetaryUnit()->format($balance->pending_amount);
                return $balance;
            });
        };

        return [
            // Summary totals
            'receivables_total_formatted' => monetaryUnit()->format($report['receivables']['total']),
            'payables_total_formatted' => monetaryUnit()->format($report['payables']['total']),
            'net_position_formatted' => monetaryUnit()->format($report['net_position']),
            'net_position' => $report['net_position'],

            // Receivables with formatted amounts
            'receivables' => [
                'total' => $report['receivables']['total'],
                'from_merchants' => $formatBalances($report['receivables']['from_merchants']),
                'from_merchants_subtotal_formatted' => monetaryUnit()->format($report['receivables']['from_merchants']->sum('pending_amount')),
                'from_couriers' => $formatBalances($report['receivables']['from_couriers']),
                'from_couriers_subtotal_formatted' => monetaryUnit()->format($report['receivables']['from_couriers']->sum('pending_amount')),
                'from_shipping' => $formatBalances($report['receivables']['from_shipping']),
                'from_shipping_subtotal_formatted' => monetaryUnit()->format($report['receivables']['from_shipping']->sum('pending_amount')),
            ],

            // Payables with formatted amounts
            'payables' => [
                'total' => $report['payables']['total'],
                'to_merchants' => $formatBalances($report['payables']['to_merchants']),
                'to_merchants_subtotal_formatted' => monetaryUnit()->format($report['payables']['to_merchants']->sum('pending_amount')),
                'to_tax_authority' => $formatBalances($report['payables']['to_tax_authority']),
                'to_tax_authority_subtotal_formatted' => monetaryUnit()->format($report['payables']['to_tax_authority']->sum('pending_amount')),
                'to_shipping' => $formatBalances($report['payables']['to_shipping']),
                'to_shipping_subtotal_formatted' => monetaryUnit()->format($report['payables']['to_shipping']->sum('pending_amount')),
            ],

            // Aging analysis
            'aging' => [
                'current_formatted' => monetaryUnit()->format($report['aging']['current']),
                '30_60_formatted' => monetaryUnit()->format($report['aging']['30_60']),
                '60_90_formatted' => monetaryUnit()->format($report['aging']['60_90']),
                'over_90_formatted' => monetaryUnit()->format($report['aging']['over_90']),
            ],
        ];
    }

    // =========================================================================
    // COURIERS REPORT FORMATTING
    // =========================================================================

    /**
     * Format couriers report for display
     * Used by AccountLedgerController::couriersReport()
     *
     * MULTI-CHANNEL: Uses monetaryUnit()->format() for centralized formatting
     */
    public function formatCouriersReport($couriers, array $totals): array
    {
        // Format totals (CENTRALIZED)
        $totalsDisplay = [
            'fees_earned_formatted' => monetaryUnit()->format($totals['fees_earned']),
            'cod_collected_formatted' => monetaryUnit()->format($totals['cod_collected']),
            'cod_pending_formatted' => monetaryUnit()->format($totals['cod_pending']),
            'owes_to_platform_formatted' => monetaryUnit()->format($totals['owes_to_platform']),
            'settlements_made_formatted' => monetaryUnit()->format($totals['settlements_made'] ?? 0),
            'delivery_count' => $totals['delivery_count'],
        ];

        // Format each courier row (CENTRALIZED)
        $couriersDisplay = $couriers->map(function ($data) {
            return [
                'courier' => $data['courier'],
                'fees_earned_formatted' => monetaryUnit()->format($data['fees_earned']),
                'cod_collected_formatted' => monetaryUnit()->format($data['cod_collected']),
                'cod_pending_formatted' => monetaryUnit()->format($data['cod_pending']),
                'settlements_made_formatted' => monetaryUnit()->format($data['settlements_made'] ?? 0),
                'owes_to_platform_formatted' => monetaryUnit()->format($data['owes_to_platform']),
                'delivery_count' => $data['delivery_count'],
                // Raw values for conditionals
                'cod_pending' => $data['cod_pending'],
                'owes_to_platform' => $data['owes_to_platform'],
            ];
        });

        return [
            'totals' => $totalsDisplay,
            'couriers' => $couriersDisplay,
        ];
    }

    // =========================================================================
    // SHIPPING COMPANIES REPORT FORMATTING
    // =========================================================================

    /**
     * Format shipping companies report for display
     * Used by AccountLedgerController::shippingCompaniesReport()
     *
     * MULTI-CHANNEL: Uses monetaryUnit()->format() for centralized formatting
     */
    public function formatShippingCompaniesReport($companies, array $totals): array
    {
        // Format totals (CENTRALIZED)
        $totalsDisplay = [
            'fees_earned_formatted' => monetaryUnit()->format($totals['fees_earned']),
            'cod_collected_formatted' => monetaryUnit()->format($totals['cod_collected']),
            'receivable_from_platform_formatted' => monetaryUnit()->format($totals['receivable_from_platform']),
            'payable_to_platform_formatted' => monetaryUnit()->format($totals['payable_to_platform']),
            'net_balance_formatted' => monetaryUnit()->format($totals['net_balance']),
            'shipment_count' => $totals['shipment_count'],
        ];

        // Format each company row (CENTRALIZED)
        $companiesDisplay = $companies->map(function ($data) {
            return [
                'company' => $data['company'],
                'fees_earned_formatted' => monetaryUnit()->format($data['fees_earned']),
                'cod_collected_formatted' => monetaryUnit()->format($data['cod_collected']),
                'receivable_from_platform_formatted' => monetaryUnit()->format($data['receivable_from_platform']),
                'payable_to_platform_formatted' => monetaryUnit()->format($data['payable_to_platform']),
                'net_balance_formatted' => monetaryUnit()->format($data['net_balance']),
                'shipment_count' => $data['shipment_count'],
                // Raw values for conditionals
                'net_balance' => $data['net_balance'],
            ];
        });

        return [
            'totals' => $totalsDisplay,
            'companies' => $companiesDisplay,
        ];
    }

    // =========================================================================
    // MERCHANTS SUMMARY REPORT FORMATTING
    // =========================================================================

    /**
     * Format merchants summary report for display
     * Used by AccountLedgerController::merchantsSummary()
     *
     * MULTI-CHANNEL: Uses monetaryUnit()->format() for centralized formatting
     */
    public function formatMerchantsSummaryReport($merchants, array $totals): array
    {
        // Format totals (CENTRALIZED)
        $totalsDisplay = [
            'total_sales_formatted' => monetaryUnit()->format($totals['total_sales']),
            'total_commission_formatted' => monetaryUnit()->format($totals['total_commission']),
            'total_tax_formatted' => monetaryUnit()->format($totals['total_tax']),
            'balance_due_formatted' => monetaryUnit()->format($totals['balance_due']),
            'net_amount_formatted' => monetaryUnit()->format($totals['net_amount']),
            'settlements_received_formatted' => monetaryUnit()->format($totals['settlements_received']),
            'transaction_count' => $totals['transaction_count'],
        ];

        // Format each merchant row (CENTRALIZED)
        $merchantsDisplay = $merchants->map(function ($data) {
            return [
                'merchant' => $data['merchant'],
                'total_sales_formatted' => monetaryUnit()->format($data['total_sales']),
                'total_commission_formatted' => monetaryUnit()->format($data['total_commission']),
                'total_tax_formatted' => monetaryUnit()->format($data['total_tax']),
                'net_receivable_formatted' => monetaryUnit()->format($data['net_receivable']),
                'settlements_received_formatted' => monetaryUnit()->format($data['settlements_received']),
                'balance_due_formatted' => monetaryUnit()->format($data['balance_due']),
                'transaction_count' => $data['transaction_count'],
                // Raw values for conditionals
                'balance_due' => $data['balance_due'],
            ];
        });

        return [
            'totals' => $totalsDisplay,
            'merchants' => $merchantsDisplay,
        ];
    }

    // =========================================================================
    // PLATFORM REPORT FORMATTING
    // =========================================================================

    /**
     * Format platform financial report for display
     * Used by AccountLedgerController::platformReport()
     *
     * MULTI-CHANNEL: Uses monetaryUnit()->format() for centralized formatting
     */
    public function formatPlatformReport(array $report): array
    {
        return [
            // Revenue section (CENTRALIZED)
            'revenue' => [
                'commission_earned_formatted' => monetaryUnit()->format($report['revenue']['commission_earned']),
                'shipping_fee_earned_formatted' => monetaryUnit()->format($report['revenue']['shipping_fee_earned'] ?? 0),
                'total_formatted' => monetaryUnit()->format($report['revenue']['total']),
            ],

            // Collections section (CENTRALIZED)
            'collections' => [
                'total_collected_formatted' => monetaryUnit()->format($report['collections']['total_collected']),
                'for_merchants_formatted' => monetaryUnit()->format($report['collections']['for_merchants']),
                'for_tax_authority_formatted' => monetaryUnit()->format($report['collections']['for_tax_authority']),
                'for_shipping_companies_formatted' => monetaryUnit()->format($report['collections']['for_shipping_companies']),
                'cod_collected_formatted' => monetaryUnit()->format($report['collections']['cod_collected']),
                'cod_pending_formatted' => monetaryUnit()->format($report['collections']['cod_pending'] ?? 0),
                // Raw values for conditionals
                'cod_collected' => $report['collections']['cod_collected'],
                'cod_pending' => $report['collections']['cod_pending'] ?? 0,
            ],

            // Liabilities section (CENTRALIZED)
            'liabilities' => [
                'to_merchants_formatted' => monetaryUnit()->format($report['liabilities']['to_merchants']),
                'to_tax_authority_formatted' => monetaryUnit()->format($report['liabilities']['to_tax_authority']),
                'to_shipping_companies_formatted' => monetaryUnit()->format($report['liabilities']['to_shipping_companies']),
                'total_formatted' => monetaryUnit()->format($report['liabilities']['total']),
            ],

            // Receivables section (CENTRALIZED)
            'receivables' => [
                'from_couriers_formatted' => monetaryUnit()->format($report['receivables']['from_couriers']),
                'from_shipping_companies_formatted' => monetaryUnit()->format($report['receivables']['from_shipping_companies']),
                'total_formatted' => monetaryUnit()->format($report['receivables']['total']),
            ],

            // Net position
            'net_position' => $report['net_position'],
            'net_position_formatted' => monetaryUnit()->format($report['net_position']),
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
