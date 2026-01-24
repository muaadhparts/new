<?php

namespace App\Domain\Accounting\Enums;

/**
 * Transaction Type Enum
 *
 * Represents types of financial transactions.
 */
enum TransactionType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
    case TRANSFER = 'transfer';
    case REFUND = 'refund';
    case COMMISSION = 'commission';
    case WITHDRAWAL = 'withdrawal';
    case DEPOSIT = 'deposit';
    case ADJUSTMENT = 'adjustment';

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::CREDIT => 'إيداع',
            self::DEBIT => 'خصم',
            self::TRANSFER => 'تحويل',
            self::REFUND => 'استرداد',
            self::COMMISSION => 'عمولة',
            self::WITHDRAWAL => 'سحب',
            self::DEPOSIT => 'إيداع نقدي',
            self::ADJUSTMENT => 'تسوية',
        };
    }

    /**
     * Get CSS color class
     */
    public function color(): string
    {
        return match($this) {
            self::CREDIT, self::DEPOSIT => 'success',
            self::DEBIT, self::WITHDRAWAL, self::COMMISSION => 'danger',
            self::TRANSFER => 'info',
            self::REFUND => 'warning',
            self::ADJUSTMENT => 'secondary',
        };
    }

    /**
     * Get sign for calculations (+1 or -1)
     */
    public function sign(): int
    {
        return match($this) {
            self::CREDIT, self::DEPOSIT, self::REFUND => 1,
            self::DEBIT, self::WITHDRAWAL, self::COMMISSION => -1,
            self::TRANSFER, self::ADJUSTMENT => 0,
        };
    }

    /**
     * Check if increases balance
     */
    public function increasesBalance(): bool
    {
        return $this->sign() === 1;
    }

    /**
     * Check if decreases balance
     */
    public function decreasesBalance(): bool
    {
        return $this->sign() === -1;
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
