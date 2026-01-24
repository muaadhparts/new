<?php

namespace App\Domain\Accounting\Schedule;

use App\Domain\Identity\Models\User;
use App\Domain\Accounting\Models\AccountingLedger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Generate Monthly Statements Task
 *
 * Generates monthly financial statements for merchants.
 */
class GenerateMonthlyStatementsTask
{
    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        $lastMonth = now()->subMonth();
        $startOfMonth = $lastMonth->copy()->startOfMonth();
        $endOfMonth = $lastMonth->copy()->endOfMonth();

        $merchants = User::where('is_merchant', 1)
            ->where('status', 1)
            ->get();

        $generated = 0;

        foreach ($merchants as $merchant) {
            $transactions = AccountingLedger::where('user_id', $merchant->id)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->orderBy('created_at')
                ->get();

            if ($transactions->isEmpty()) {
                continue;
            }

            $statement = [
                'merchant_id' => $merchant->id,
                'merchant_name' => $merchant->name,
                'period' => $lastMonth->format('F Y'),
                'generated_at' => now()->toIso8601String(),
                'opening_balance' => $transactions->first()->balance_before ?? 0,
                'closing_balance' => $transactions->last()->balance_after ?? 0,
                'total_credits' => $transactions->where('type', 'credit')->sum('amount'),
                'total_debits' => $transactions->where('type', 'debit')->sum('amount'),
                'transaction_count' => $transactions->count(),
            ];

            $filename = sprintf(
                'statements/%d/%s-statement.json',
                $merchant->id,
                $lastMonth->format('Y-m')
            );

            Storage::put($filename, json_encode($statement, JSON_PRETTY_PRINT));
            $generated++;
        }

        Log::info('Monthly statements generated', [
            'period' => $lastMonth->format('F Y'),
            'merchants_processed' => $merchants->count(),
            'statements_generated' => $generated,
        ]);
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'monthlyOn';
    }

    /**
     * Get the schedule day.
     */
    public static function day(): int
    {
        return 1;
    }

    /**
     * Get the schedule time.
     */
    public static function at(): string
    {
        return '06:00';
    }
}
