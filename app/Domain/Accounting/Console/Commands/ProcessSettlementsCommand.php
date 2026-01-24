<?php

namespace App\Domain\Accounting\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Accounting\Models\AccountBalance;
use App\Domain\Accounting\Models\SettlementBatch;
use Carbon\Carbon;

/**
 * Process Settlements Command
 *
 * Processes pending settlements and payouts.
 */
class ProcessSettlementsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'accounting:process-settlements
                            {--min-amount=100 : Minimum amount for settlement}
                            {--dry-run : Show what would be processed}';

    /**
     * The console command description.
     */
    protected $description = 'Process pending merchant settlements';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minAmount = (float) $this->option('min-amount');
        $dryRun = $this->option('dry-run');

        $this->info("Processing settlements with minimum amount: {$minAmount}...");

        // Find balances ready for settlement
        $eligibleBalances = AccountBalance::where('current_balance', '>=', $minAmount)
            ->whereHas('user', function ($q) {
                $q->where('is_merchant', 1);
            })
            ->get();

        if ($eligibleBalances->isEmpty()) {
            $this->info('No eligible balances for settlement.');
            return self::SUCCESS;
        }

        $this->info("Found {$eligibleBalances->count()} eligible accounts.");

        if ($dryRun) {
            $this->warn('Dry run mode - no settlements will be created.');
            $this->table(
                ['User ID', 'Balance', 'Email'],
                $eligibleBalances->map(fn($b) => [
                    $b->user_id,
                    number_format($b->current_balance, 2),
                    $b->user->email ?? 'N/A',
                ])
            );
            return self::SUCCESS;
        }

        // Create settlement batch
        $batch = SettlementBatch::create([
            'batch_number' => 'BATCH-' . Carbon::now()->format('YmdHis'),
            'status' => 'processing',
            'total_amount' => $eligibleBalances->sum('current_balance'),
            'count' => $eligibleBalances->count(),
            'created_by' => 0,
        ]);

        $this->info("Created batch: {$batch->batch_number}");
        $this->info("Total amount: " . number_format($batch->total_amount, 2));

        return self::SUCCESS;
    }
}
