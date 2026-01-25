<?php

namespace App\Domain\Merchant\Actions;

use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantStockUpdate;
use Illuminate\Support\Facades\DB;

/**
 * UpdateStockAction - Update merchant item stock
 *
 * Single-responsibility action for stock updates.
 * Logs all stock changes for audit trail.
 */
class UpdateStockAction
{
    /**
     * Execute the action
     *
     * @param int $merchantItemId Merchant item ID
     * @param int $newStock New stock quantity
     * @param string $reason Reason for update
     * @param int|null $operatorId Operator performing the update
     * @return array{success: bool, message: string, item?: MerchantItem}
     */
    public function execute(
        int $merchantItemId,
        int $newStock,
        string $reason = 'manual',
        ?int $operatorId = null
    ): array {
        $merchantItem = MerchantItem::find($merchantItemId);

        if (!$merchantItem) {
            return [
                'success' => false,
                'message' => __('Item not found'),
            ];
        }

        if ($newStock < 0) {
            return [
                'success' => false,
                'message' => __('Stock cannot be negative'),
            ];
        }

        $oldStock = $merchantItem->stock;

        try {
            DB::transaction(function () use ($merchantItem, $newStock, $oldStock, $reason, $operatorId) {
                // Update stock
                $merchantItem->stock = $newStock;
                $merchantItem->save();

                // Log the change
                MerchantStockUpdate::create([
                    'merchant_item_id' => $merchantItem->id,
                    'user_id' => $merchantItem->user_id,
                    'previous_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'change' => $newStock - $oldStock,
                    'reason' => $reason,
                    'operator_id' => $operatorId,
                ]);
            });

            return [
                'success' => true,
                'message' => __('Stock updated successfully'),
                'item' => $merchantItem->fresh(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Increment stock
     *
     * @param int $merchantItemId
     * @param int $quantity
     * @param string $reason
     * @return array
     */
    public function increment(int $merchantItemId, int $quantity, string $reason = 'restock'): array
    {
        $merchantItem = MerchantItem::find($merchantItemId);

        if (!$merchantItem) {
            return [
                'success' => false,
                'message' => __('Item not found'),
            ];
        }

        return $this->execute(
            $merchantItemId,
            $merchantItem->stock + $quantity,
            $reason
        );
    }

    /**
     * Decrement stock
     *
     * @param int $merchantItemId
     * @param int $quantity
     * @param string $reason
     * @return array
     */
    public function decrement(int $merchantItemId, int $quantity, string $reason = 'sale'): array
    {
        $merchantItem = MerchantItem::find($merchantItemId);

        if (!$merchantItem) {
            return [
                'success' => false,
                'message' => __('Item not found'),
            ];
        }

        $newStock = max(0, $merchantItem->stock - $quantity);

        return $this->execute($merchantItemId, $newStock, $reason);
    }
}
