<?php

namespace App\Domain\Merchant\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Merchant\Models\MerchantItem;

/**
 * Out of Stock Notification
 *
 * Sent to merchant when item goes out of stock.
 */
class OutOfStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected MerchantItem $merchantItem
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
            ->subject(__('notifications.merchant.out_of_stock_subject', ['item' => $itemName]))
            ->greeting(__('notifications.merchant.out_of_stock_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.merchant.out_of_stock_line1', ['item' => $itemName]))
            ->line(__('notifications.merchant.out_of_stock_line2'))
            ->action(__('notifications.merchant.restock_now'), url('/merchant/inventory/' . $this->merchantItem->id))
            ->line(__('notifications.merchant.out_of_stock_warning'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'out_of_stock',
            'merchant_item_id' => $this->merchantItem->id,
            'catalog_item_id' => $this->merchantItem->catalog_item_id,
            'item_name' => $this->merchantItem->catalogItem->name ?? null,
        ];
    }

    /**
     * Get the merchant item
     */
    public function getMerchantItem(): MerchantItem
    {
        return $this->merchantItem;
    }
}
