<?php

namespace App\Domain\Merchant\Schedule;

use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Support\Facades\Log;

/**
 * Update Stock Status Task
 *
 * Updates stock status flags based on current quantities.
 */
class UpdateStockStatusTask
{
    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        // Mark items as out of stock
        $outOfStock = MerchantItem::where('status', 1)
            ->where('stock', '<=', 0)
            ->update(['stock_status' => 'out_of_stock']);

        // Mark items as low stock (1-5)
        $lowStock = MerchantItem::where('status', 1)
            ->where('stock', '>', 0)
            ->where('stock', '<=', 5)
            ->update(['stock_status' => 'low_stock']);

        // Mark items as in stock (>5)
        $inStock = MerchantItem::where('status', 1)
            ->where('stock', '>', 5)
            ->update(['stock_status' => 'in_stock']);

        Log::info('Stock status updated', [
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'in_stock' => $inStock,
        ]);
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'everyFifteenMinutes';
    }
}
