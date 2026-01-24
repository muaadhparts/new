<?php

namespace App\Domain\Accounting\Contracts;

use App\Models\AccountParty;
use App\Models\AccountingLedger;
use Illuminate\Support\Collection;

/**
 * AccountLedgerInterface - Contract for ledger operations
 *
 * All accounting entries MUST go through this interface.
 */
interface AccountLedgerInterface
{
    /**
     * Get or create platform party
     */
    public function getPlatformParty(): AccountParty;

    /**
     * Get or create merchant party
     */
    public function getMerchantParty(int $merchantId): AccountParty;

    /**
     * Get or create courier party
     */
    public function getCourierParty(int $courierId): AccountParty;

    /**
     * Record a debt entry
     */
    public function recordDebt(
        AccountParty $debtor,
        AccountParty $creditor,
        float $amount,
        string $type,
        ?int $purchaseId = null,
        ?string $description = null
    ): AccountingLedger;

    /**
     * Record a settlement entry
     */
    public function recordSettlement(
        AccountParty $from,
        AccountParty $to,
        float $amount,
        ?int $batchId = null,
        ?string $reference = null
    ): AccountingLedger;

    /**
     * Get party balance summary
     */
    public function getPartySummary(AccountParty $party): array;

    /**
     * Get ledger entries for a party
     */
    public function getPartyLedger(AccountParty $party, ?string $startDate = null, ?string $endDate = null): Collection;
}
