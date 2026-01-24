<?php

namespace App\Domain\Merchant\Schedule;

use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantSetting;
use App\Domain\Merchant\Notifications\LowStockNotification;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Notify Low Stock Task
 *
 * Sends notifications to merchants about low stock items.
 */
class NotifyLowStockTask
{
    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        $merchants = User::where('is_merchant', 1)
            ->where('status', 1)
            ->get();

        $notified = 0;

        foreach ($merchants as $merchant) {
            $threshold = MerchantSetting::where('user_id', $merchant->id)
                ->value('low_stock_threshold') ?? 5;

            $lowStockItems = MerchantItem::where('user_id', $merchant->id)
                ->where('status', 1)
                ->where('stock', '>', 0)
                ->where('stock', '<=', $threshold)
                ->with('catalogItem:id,name,name_ar,sku')
                ->get();

            if ($lowStockItems->isNotEmpty()) {
                $merchant->notify(new LowStockNotification($lowStockItems));
                $notified++;
            }
        }

        Log::info('Low stock notifications sent', [
            'merchants_checked' => $merchants->count(),
            'merchants_notified' => $notified,
        ]);
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'dailyAt';
    }

    /**
     * Get the schedule time.
     */
    public static function at(): string
    {
        return '08:00';
    }
}
