<?php

namespace App\Domain\Merchant\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Commerce\Models\MerchantPurchase;

/**
 * New Order Notification
 *
 * Sent to merchant when they receive a new order.
 */
class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected MerchantPurchase $merchantPurchase
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
        return (new MailMessage)
            ->subject(__('notifications.merchant.new_order_subject'))
            ->greeting(__('notifications.merchant.new_order_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.merchant.new_order_line1', [
                'order' => $this->merchantPurchase->purchase->order_number ?? $this->merchantPurchase->id,
            ]))
            ->line(__('notifications.merchant.new_order_line2', [
                'total' => monetaryUnit()->format($this->merchantPurchase->subtotal),
                'items' => $this->merchantPurchase->items_count ?? 1,
            ]))
            ->action(__('notifications.merchant.view_order'), url('/merchant/orders/' . $this->merchantPurchase->id))
            ->line(__('notifications.merchant.process_order'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_order',
            'merchant_purchase_id' => $this->merchantPurchase->id,
            'purchase_id' => $this->merchantPurchase->purchase_id,
            'subtotal' => $this->merchantPurchase->subtotal,
        ];
    }

    /**
     * Get the merchant purchase
     */
    public function getMerchantPurchase(): MerchantPurchase
    {
        return $this->merchantPurchase;
    }
}
