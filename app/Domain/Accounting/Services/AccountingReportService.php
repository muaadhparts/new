<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\AccountParty;
use App\Domain\Accounting\Models\AccountingLedger;
use App\Domain\Accounting\Models\AccountBalance;
use App\Domain\Platform\Models\MonetaryUnit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * AccountingReportService - خدمة التقارير المحاسبية الرسمية
 *
 * Domain: Accounting
 *
 * جميع التقارير تعتمد 100% على Ledger
 * لا قراءة مباشرة من أي جدول آخر
 */
class AccountingReportService
{
    protected AccountingEntryService $entryService;

    public function __construct(AccountingEntryService $entryService)
    {
        $this->entryService = $entryService;
    }

    // ═══════════════════════════════════════════════════════════════
    // PLATFORM REPORT - تقرير المنصة
    // ═══════════════════════════════════════════════════════════════

    /**
     * تقرير المنصة الشامل
     */
    public function getPlatformReport(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $platform = $this->entryService->getPlatformParty();

        $query = AccountingLedger::where(function ($q) use ($platform) {
            $q->where('from_party_id', $platform->id)
                ->orWhere('to_party_id', $platform->id);
        });

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $entries = $query->get();

        // ═══ الإيرادات ═══
        $commissionEarned = $entries
            ->where('entry_type', AccountingLedger::ENTRY_COMMISSION_EARNED)
            ->where('to_party_id', $platform->id)
            ->sum('amount');

        $shippingFeeEarned = $entries
            ->where('entry_type', AccountingLedger::ENTRY_SHIPPING_FEE_PLATFORM)
            ->where('to_party_id', $platform->id)
            ->sum('amount');

        // ═══ المبالغ المحصلة ═══
        $salesCollected = $entries
            ->where('entry_type', AccountingLedger::ENTRY_SALE_REVENUE)
            ->where('debt_status', AccountingLedger::DEBT_SETTLED)
            ->sum('amount');

        $taxCollected = $entries
            ->where('entry_type', AccountingLedger::ENTRY_TAX_COLLECTED)
            ->sum('amount');

        $shippingCollected = $entries
            ->where('entry_type', AccountingLedger::ENTRY_SHIPPING_FEE_PLATFORM)
            ->sum('amount');

        $codCollected = $entries
            ->where('entry_type', AccountingLedger::ENTRY_COD_COLLECTED)
            ->where('to_party_id', $platform->id)
            ->sum('amount');

        // ═══ الالتزامات ═══
        $merchantsPayable = AccountBalance::where('party_id', $platform->id)
            ->where('balance_type', 'payable')
            ->whereHas('counterparty', function ($q) {
                $q->where('party_type', 'merchant');
            })
            ->sum('pending_amount');

        $taxPayable = AccountBalance::where('party_id', $platform->id)
            ->where('balance_type', 'payable')
            ->whereHas('counterparty', function ($q) {
                $q->where('party_type', 'tax_authority');
            })
            ->sum('pending_amount');

        $shippingPayable = AccountBalance::where('party_id', $platform->id)
            ->where('balance_type', 'payable')
            ->whereHas('counterparty', function ($q) {
                $q->where('party_type', 'shipping_provider');
            })
            ->sum('pending_amount');

        // ═══ التسويات ═══
        $settlementsToMerchants = $entries
            ->where('entry_type', AccountingLedger::ENTRY_SETTLEMENT_PAYMENT)
            ->where('from_party_id', $platform->id)
            ->whereIn('to_party_id', AccountParty::where('party_type', 'merchant')->pluck('id'))
            ->sum('amount');

        // ═══ المستحقات ═══
        $receivableFromCouriers = AccountBalance::where('party_id', $platform->id)
            ->where('balance_type', 'receivable')
            ->whereHas('counterparty', function ($q) {
                $q->where('party_type', 'courier');
            })
            ->sum('pending_amount');

        $receivableFromShipping = AccountBalance::where('party_id', $platform->id)
            ->where('balance_type', 'receivable')
            ->whereHas('counterparty', function ($q) {
                $q->where('party_type', 'shipping_provider');
            })
            ->sum('pending_amount');

        $codPending = $entries
            ->where('entry_type', AccountingLedger::ENTRY_COD_PENDING)
            ->where('debt_status', AccountingLedger::DEBT_PENDING)
            ->sum('amount');

        return [
            'period' => [
                'start' => $startDate?->format('Y-m-d'),
                'end' => $endDate?->format('Y-m-d'),
            ],
            'revenue' => [
                'commission_earned' => $commissionEarned,
                'shipping_fee_earned' => $shippingFeeEarned,
                'total' => $commissionEarned + $shippingFeeEarned,
            ],
            'collections' => [
                'total_collected' => $salesCollected + $taxCollected + $shippingCollected,
                'for_merchants' => $salesCollected - $commissionEarned,
                'for_tax_authority' => $taxCollected,
                'for_shipping_companies' => $shippingCollected,
                'cod_collected' => $codCollected,
                'cod_pending' => $codPending,
            ],
            'liabilities' => [
                'to_merchants' => $merchantsPayable,
                'to_tax_authority' => $taxPayable,
                'to_shipping_companies' => $shippingPayable,
                'total' => $merchantsPayable + $taxPayable + $shippingPayable,
            ],
            'receivables' => [
                'from_couriers' => $receivableFromCouriers,
                'from_shipping_companies' => $receivableFromShipping,
                'total' => $receivableFromCouriers + $receivableFromShipping,
            ],
            'settlements' => [
                'to_merchants' => $settlementsToMerchants,
            ],
            'net_position' => $commissionEarned
                + $shippingFeeEarned
                + $receivableFromCouriers
                + $receivableFromShipping
                - $merchantsPayable
                - $taxPayable
                - $shippingPayable,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // TAX REPORT - تقرير الضرائب
    // ═══════════════════════════════════════════════════════════════

    /**
     * تقرير الضرائب الرسمي
     */
    public function getTaxReport(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $query = AccountingLedger::where('entry_type', AccountingLedger::ENTRY_TAX_COLLECTED);

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $entries = $query->with(['merchantPurchase.merchant'])
            ->orderBy('transaction_date', 'desc')
            ->get();

        $byLocation = $entries->groupBy(function ($entry) {
            return $entry->metadata['tax_location'] ?? __('Unknown');
        })->map(function ($locationEntries, $location) {
            $taxRate = $locationEntries->first()->metadata['tax_rate'] ?? 0;

            return [
                'location' => $location,
                'tax_rate' => $taxRate,
                'transaction_count' => $locationEntries->count(),
                'taxable_amount' => $locationEntries->sum(function ($e) {
                    return $e->metadata['taxable_amount'] ?? 0;
                }),
                'tax_collected' => $locationEntries->sum('amount'),
                'tax_pending' => $locationEntries
                    ->where('debt_status', AccountingLedger::DEBT_PENDING)
                    ->sum('amount'),
                'tax_remitted' => $locationEntries
                    ->where('debt_status', AccountingLedger::DEBT_SETTLED)
                    ->sum('amount'),
            ];
        })->values();

        $byMonth = $entries->groupBy(function ($entry) {
            return $entry->transaction_date->format('Y-m');
        })->map(function ($monthEntries, $month) {
            return [
                'month' => $month,
                'transaction_count' => $monthEntries->count(),
                'tax_collected' => $monthEntries->sum('amount'),
            ];
        })->values();

        $totals = [
            'collected' => $entries->sum('amount'),
            'pending' => $entries->where('debt_status', AccountingLedger::DEBT_PENDING)->sum('amount'),
            'remitted' => $entries->where('debt_status', AccountingLedger::DEBT_SETTLED)->sum('amount'),
            'transaction_count' => $entries->count(),
        ];

        return [
            'period' => [
                'start' => $startDate?->format('Y-m-d'),
                'end' => $endDate?->format('Y-m-d'),
            ],
            'by_location' => $byLocation,
            'by_month' => $byMonth,
            'totals' => $totals,
            'entries' => $entries,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // RECEIVABLES/PAYABLES REPORT - تقرير الذمم
    // ═══════════════════════════════════════════════════════════════

    /**
     * تقرير الذمم المدينة والدائنة
     */
    public function getReceivablesPayablesReport(): array
    {
        $platform = $this->entryService->getPlatformParty();

        $receivables = [
            'from_merchants' => $this->getReceivablesFrom($platform, 'merchant'),
            'from_couriers' => $this->getReceivablesFrom($platform, 'courier'),
            'from_shipping' => $this->getReceivablesFrom($platform, 'shipping_provider'),
        ];

        $receivables['total'] = collect($receivables)->sum(function ($group) {
            return $group->sum('pending_amount');
        });

        $payables = [
            'to_merchants' => $this->getPayablesTo($platform, 'merchant'),
            'to_tax_authority' => $this->getPayablesTo($platform, 'tax_authority'),
            'to_shipping' => $this->getPayablesTo($platform, 'shipping_provider'),
            'to_couriers' => $this->getPayablesTo($platform, 'courier'),
        ];

        $payables['total'] = collect($payables)->sum(function ($group) {
            if ($group instanceof Collection) {
                return $group->sum('pending_amount');
            }
            return 0;
        });

        $aging = $this->calculateAging($platform);

        return [
            'receivables' => $receivables,
            'payables' => $payables,
            'net_position' => $receivables['total'] - $payables['total'],
            'aging' => $aging,
        ];
    }

    protected function getReceivablesFrom(AccountParty $platform, string $partyType): Collection
    {
        return AccountBalance::where('party_id', $platform->id)
            ->where('balance_type', 'receivable')
            ->where('pending_amount', '>', 0)
            ->whereHas('counterparty', function ($q) use ($partyType) {
                $q->where('party_type', $partyType);
            })
            ->with('counterparty')
            ->get();
    }

    protected function getPayablesTo(AccountParty $platform, string $partyType): Collection
    {
        return AccountBalance::where('party_id', $platform->id)
            ->where('balance_type', 'payable')
            ->where('pending_amount', '>', 0)
            ->whereHas('counterparty', function ($q) use ($partyType) {
                $q->where('party_type', $partyType);
            })
            ->with('counterparty')
            ->get();
    }

    protected function calculateAging(AccountParty $platform): array
    {
        $today = now();

        $pendingEntries = AccountingLedger::where(function ($q) use ($platform) {
            $q->where('from_party_id', $platform->id)
                ->orWhere('to_party_id', $platform->id);
        })
            ->where('debt_status', AccountingLedger::DEBT_PENDING)
            ->get();

        $aging = [
            'current' => 0,
            '30_60' => 0,
            '60_90' => 0,
            'over_90' => 0,
        ];

        foreach ($pendingEntries as $entry) {
            $days = $entry->transaction_date->diffInDays($today);

            if ($days <= 30) {
                $aging['current'] += $entry->amount;
            } elseif ($days <= 60) {
                $aging['30_60'] += $entry->amount;
            } elseif ($days <= 90) {
                $aging['60_90'] += $entry->amount;
            } else {
                $aging['over_90'] += $entry->amount;
            }
        }

        return $aging;
    }

    // ═══════════════════════════════════════════════════════════════
    // MERCHANT SUMMARY REPORT - ملخص التجار
    // ═══════════════════════════════════════════════════════════════

    public function getMerchantsSummaryReport(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        $merchants = AccountParty::where('party_type', 'merchant')
            ->where('is_active', true)
            ->get();

        return $merchants->map(function ($merchant) use ($startDate, $endDate) {
            $query = AccountingLedger::where(function ($q) use ($merchant) {
                $q->where('from_party_id', $merchant->id)
                    ->orWhere('to_party_id', $merchant->id);
            });

            if ($startDate) {
                $query->where('transaction_date', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('transaction_date', '<=', $endDate);
            }

            $entries = $query->get();

            $totalSales = $entries
                ->where('entry_type', AccountingLedger::ENTRY_SALE_REVENUE)
                ->where('to_party_id', $merchant->id)
                ->sum('amount');

            $totalCommission = $entries
                ->where('entry_type', AccountingLedger::ENTRY_COMMISSION_EARNED)
                ->where('from_party_id', $merchant->id)
                ->sum('amount');

            $totalTax = $entries
                ->where('entry_type', AccountingLedger::ENTRY_TAX_COLLECTED)
                ->where('from_party_id', $merchant->id)
                ->sum('amount');

            $settlementsReceived = $entries
                ->where('entry_type', AccountingLedger::ENTRY_SETTLEMENT_PAYMENT)
                ->where('to_party_id', $merchant->id)
                ->sum('amount');

            $netReceivable = $totalSales - $totalCommission - $totalTax;
            $balanceDue = $netReceivable - $settlementsReceived;

            return [
                'merchant' => $merchant,
                'total_sales' => $totalSales,
                'total_commission' => $totalCommission,
                'total_tax' => $totalTax,
                'net_receivable' => $netReceivable,
                'settlements_received' => $settlementsReceived,
                'balance_due' => $balanceDue,
                'transaction_count' => $entries->count(),
            ];
        })->sortByDesc('total_sales');
    }

    // ═══════════════════════════════════════════════════════════════
    // COURIER REPORT - تقرير المناديب
    // ═══════════════════════════════════════════════════════════════

    public function getCouriersReport(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        $couriers = AccountParty::where('party_type', 'courier')
            ->where('is_active', true)
            ->get();

        return $couriers->map(function ($courier) use ($startDate, $endDate) {
            $query = AccountingLedger::where(function ($q) use ($courier) {
                $q->where('from_party_id', $courier->id)
                    ->orWhere('to_party_id', $courier->id);
            });

            if ($startDate) {
                $query->where('transaction_date', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('transaction_date', '<=', $endDate);
            }

            $entries = $query->get();

            $feesEarned = $entries
                ->where('entry_type', AccountingLedger::ENTRY_COURIER_FEE)
                ->where('to_party_id', $courier->id)
                ->sum('amount');

            $codCollected = $entries
                ->where('entry_type', AccountingLedger::ENTRY_COD_COLLECTED)
                ->where('from_party_id', $courier->id)
                ->sum('amount');

            $codPending = $entries
                ->where('entry_type', AccountingLedger::ENTRY_COD_PENDING)
                ->where('from_party_id', $courier->id)
                ->where('debt_status', AccountingLedger::DEBT_PENDING)
                ->sum('amount');

            $settlementsMade = $entries
                ->where('entry_type', AccountingLedger::ENTRY_SETTLEMENT_PAYMENT)
                ->where('from_party_id', $courier->id)
                ->sum('amount');

            $platform = $this->entryService->getPlatformParty();
            $owesToPlatform = AccountBalance::where('party_id', $courier->id)
                ->where('counterparty_id', $platform->id)
                ->where('balance_type', 'payable')
                ->value('pending_amount') ?? 0;

            return [
                'courier' => $courier,
                'fees_earned' => $feesEarned,
                'cod_collected' => $codCollected,
                'cod_pending' => $codPending,
                'settlements_made' => $settlementsMade,
                'owes_to_platform' => $owesToPlatform,
                'delivery_count' => $entries
                    ->whereIn('entry_type', [
                        AccountingLedger::ENTRY_COD_COLLECTED,
                        AccountingLedger::ENTRY_COURIER_FEE,
                    ])
                    ->unique('merchant_purchase_id')
                    ->count(),
            ];
        })->sortByDesc('cod_collected');
    }

    // ═══════════════════════════════════════════════════════════════
    // SHIPPING COMPANIES REPORT - تقرير شركات الشحن
    // ═══════════════════════════════════════════════════════════════

    public function getShippingCompaniesReport(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        $shippingCompanies = AccountParty::where('party_type', 'shipping_provider')
            ->where('is_active', true)
            ->get();

        return $shippingCompanies->map(function ($company) use ($startDate, $endDate) {
            $query = AccountingLedger::where(function ($q) use ($company) {
                $q->where('from_party_id', $company->id)
                    ->orWhere('to_party_id', $company->id);
            });

            if ($startDate) {
                $query->where('transaction_date', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('transaction_date', '<=', $endDate);
            }

            $entries = $query->get();

            $feesEarned = $entries
                ->where('entry_type', AccountingLedger::ENTRY_SHIPPING_FEE_PLATFORM)
                ->where('to_party_id', $company->id)
                ->sum('amount');

            $codCollected = $entries
                ->where('entry_type', AccountingLedger::ENTRY_COD_COLLECTED)
                ->where('from_party_id', $company->id)
                ->sum('amount');

            $platform = $this->entryService->getPlatformParty();
            $receivableFromPlatform = AccountBalance::where('party_id', $company->id)
                ->where('counterparty_id', $platform->id)
                ->where('balance_type', 'receivable')
                ->value('pending_amount') ?? 0;

            $payableToPlatform = AccountBalance::where('party_id', $company->id)
                ->where('counterparty_id', $platform->id)
                ->where('balance_type', 'payable')
                ->value('pending_amount') ?? 0;

            return [
                'company' => $company,
                'fees_earned' => $feesEarned,
                'cod_collected' => $codCollected,
                'receivable_from_platform' => $receivableFromPlatform,
                'payable_to_platform' => $payableToPlatform,
                'net_balance' => $receivableFromPlatform - $payableToPlatform,
                'shipment_count' => $entries
                    ->where('entry_type', AccountingLedger::ENTRY_SHIPPING_FEE_PLATFORM)
                    ->unique('merchant_purchase_id')
                    ->count(),
            ];
        })->sortByDesc('fees_earned');
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    public function getDefaultMonetaryUnit(): MonetaryUnit
    {
        return monetaryUnit()->getDefault();
    }
}
