<?php

namespace App\Domain\Accounting\Traits;

use App\Domain\Accounting\Models\AccountBalance;
use App\Domain\Accounting\Enums\TransactionType;

/**
 * Has Balance Trait
 *
 * Provides account balance functionality.
 */
trait HasBalance
{
    /**
     * Get account balance relationship
     */
    public function accountBalance()
    {
        return $this->hasOne(AccountBalance::class, 'user_id');
    }

    /**
     * Get or create account balance
     */
    public function getOrCreateAccountBalance(): AccountBalance
    {
        return $this->accountBalance()->firstOrCreate(
            ['user_id' => $this->id],
            [
                'current_balance' => 0,
                'pending_balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
            ]
        );
    }

    /**
     * Get current balance
     */
    public function getCurrentBalance(): float
    {
        return (float) ($this->accountBalance?->current_balance ?? 0);
    }

    /**
     * Get pending balance
     */
    public function getPendingBalance(): float
    {
        return (float) ($this->accountBalance?->pending_balance ?? 0);
    }

    /**
     * Get total balance (current + pending)
     */
    public function getTotalBalance(): float
    {
        return $this->getCurrentBalance() + $this->getPendingBalance();
    }

    /**
     * Get total earned
     */
    public function getTotalEarned(): float
    {
        return (float) ($this->accountBalance?->total_earned ?? 0);
    }

    /**
     * Get total withdrawn
     */
    public function getTotalWithdrawn(): float
    {
        return (float) ($this->accountBalance?->total_withdrawn ?? 0);
    }

    /**
     * Check if has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->getCurrentBalance() >= $amount;
    }

    /**
     * Credit balance
     */
    public function creditBalance(float $amount, string $reason = null): bool
    {
        $balance = $this->getOrCreateAccountBalance();

        return $balance->update([
            'current_balance' => $balance->current_balance + $amount,
            'total_earned' => $balance->total_earned + $amount,
        ]);
    }

    /**
     * Debit balance
     */
    public function debitBalance(float $amount, string $reason = null): bool
    {
        if (!$this->hasSufficientBalance($amount)) {
            return false;
        }

        $balance = $this->getOrCreateAccountBalance();

        return $balance->update([
            'current_balance' => $balance->current_balance - $amount,
        ]);
    }

    /**
     * Add to pending balance
     */
    public function addToPendingBalance(float $amount): bool
    {
        $balance = $this->getOrCreateAccountBalance();

        return $balance->update([
            'pending_balance' => $balance->pending_balance + $amount,
        ]);
    }

    /**
     * Release pending to current
     */
    public function releasePendingBalance(float $amount): bool
    {
        $balance = $this->getOrCreateAccountBalance();

        if ($balance->pending_balance < $amount) {
            return false;
        }

        return $balance->update([
            'pending_balance' => $balance->pending_balance - $amount,
            'current_balance' => $balance->current_balance + $amount,
        ]);
    }

    /**
     * Record withdrawal
     */
    public function recordWithdrawal(float $amount): bool
    {
        if (!$this->hasSufficientBalance($amount)) {
            return false;
        }

        $balance = $this->getOrCreateAccountBalance();

        return $balance->update([
            'current_balance' => $balance->current_balance - $amount,
            'total_withdrawn' => $balance->total_withdrawn + $amount,
        ]);
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalance(string $currency = 'SAR'): string
    {
        return number_format($this->getCurrentBalance(), 2) . ' ' . $currency;
    }
}
