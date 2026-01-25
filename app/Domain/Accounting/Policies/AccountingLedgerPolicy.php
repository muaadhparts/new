<?php

namespace App\Domain\Accounting\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Accounting\Models\AccountingLedger;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Accounting Ledger Policy
 *
 * Determines authorization for ledger/transaction viewing.
 */
class AccountingLedgerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any ledger entries.
     */
    public function viewAny(User $user): bool
    {
        // Merchants can view their ledger
        return $user->is_merchant ?? false;
    }

    /**
     * Determine if user can view the ledger entry.
     */
    public function view(User $user, AccountingLedger $entry): bool
    {
        return $user->id === $entry->user_id;
    }

    /**
     * Determine if user can export ledger.
     */
    public function export(User $user): bool
    {
        return $user->is_merchant ?? false;
    }

    /**
     * Determine if user can view statements.
     */
    public function viewStatements(User $user): bool
    {
        return $user->is_merchant ?? false;
    }
}
