<?php

namespace App\Domain\Merchant\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Enums\StockStatus;

/**
 * Update Stock Status Command
 *
 * Updates stock status for all merchant items.
 */
class UpdateStockStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'merchant:update-stock-status
                            {--merchant= : Specific merchant ID}
                            {--low-threshold=5 : Low stock threshold}';

    /**
     * The console command description.
     */
    protected $description = 'Update stock status for merchant items';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $merchantId = $this->option('merchant');
        $threshold = (int) $this->option('low-threshold');

        $this->info('Updating stock statuses...');

        $query = MerchantItem::query();

        if ($merchantId) {
            $query->where('user_id', $merchantId);
        }

        $outOfStock = 0;
        $lowStock = 0;
        $inStock = 0;

        $query->chunk(100, function ($items) use ($threshold, &$outOfStock, &$lowStock, &$inStock) {
            foreach ($items as $item) {
                $status = StockStatus::fromQuantity($item->stock, $threshold);

                match ($status) {
                    StockStatus::OUT_OF_STOCK => $outOfStock++,
                    StockStatus::LOW_STOCK => $lowStock++,
                    StockStatus::IN_STOCK => $inStock++,
                    default => null,
                };
            }
        });

        $this->table(
            ['Status', 'Count'],
            [
                ['In Stock', $inStock],
                ['Low Stock', $lowStock],
                ['Out of Stock', $outOfStock],
            ]
        );

        $this->info('Stock status update complete.');

        return self::SUCCESS;
    }
}
