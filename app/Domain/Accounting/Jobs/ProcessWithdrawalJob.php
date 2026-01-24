<?php

namespace App\Domain\Accounting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Accounting\Models\Withdraw;
use App\Domain\Accounting\Models\AccountBalance;

/**
 * Process Withdrawal Job
 *
 * Processes a withdrawal request.
 */
class ProcessWithdrawalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Withdraw $withdrawal
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Validate balance
        $balance = AccountBalance::where('user_id', $this->withdrawal->user_id)->first();

        if (!$balance || $balance->current_balance < $this->withdrawal->amount) {
            $this->withdrawal->update([
                'status' => 'rejected',
                'reject_reason' => 'Insufficient balance',
            ]);
            return;
        }

        // Deduct from balance
        $balance->decrement('current_balance', $this->withdrawal->amount);
        $balance->increment('total_withdrawn', $this->withdrawal->amount);

        // Mark as processing
        $this->withdrawal->update(['status' => 'processing']);

        // In real implementation, initiate bank transfer here

        // Mark as completed (simulated)
        $this->withdrawal->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);

        \Log::info('Withdrawal processed', [
            'withdrawal_id' => $this->withdrawal->id,
            'user_id' => $this->withdrawal->user_id,
            'amount' => $this->withdrawal->amount,
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->withdrawal->update([
            'status' => 'failed',
            'reject_reason' => $exception->getMessage(),
        ]);

        \Log::error('Withdrawal failed', [
            'withdrawal_id' => $this->withdrawal->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
