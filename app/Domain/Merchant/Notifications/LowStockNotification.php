<?php

namespace App\Domain\Merchant\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Merchant\Models\MerchantItem;

/**
 * Low Stock Notification
 *
 * Sent to merchant when item stock falls below threshold.
 */
class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected MerchantItem $merchantItem,
        protected int $currentStock,
        protected int $threshold = 5
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $itemName = $this->merchantItem->catalogItem->name ?? 'Item #' . $this->merchantItem->id;

        return (new MailMessage)
            ->subject(__('notifications.merchant.low_stock_subject', ['item' => $itemName]))
            ->greeting(__('notifications.merchant.low_stock_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.merchant.low_stock_line1', [
                'item' => $itemName,
                'stock' => $this->currentStock,
            ]))
            ->line(__('notifications.merchant.low_stock_line2', ['threshold' => $this->threshold]))
            ->action(__('notifications.merchant.manage_inventory'), url('/merchant/inventory/' . $this->merchantItem->id))
            ->line(__('notifications.merchant.restock_reminder'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'merchant_item_id' => $this->merchantItem->id,
            'catalog_item_id' => $this->merchantItem->catalog_item_id,
            'current_stock' => $this->currentStock,
            'threshold' => $this->threshold,
        ];
    }

    /**
     * Get the merchant item
     */
    public function getMerchantItem(): MerchantItem
    {
        return $this->merchantItem;
    }

    /**
     * Get current stock
     */
    public function getCurrentStock(): int
    {
        return $this->currentStock;
    }

    /**
     * Get threshold
     */
    public function getThreshold(): int
    {
        return $this->threshold;
    }
}
