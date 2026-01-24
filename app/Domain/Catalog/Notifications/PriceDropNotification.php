<?php

namespace App\Domain\Catalog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Catalog\Models\CatalogItem;

/**
 * Price Drop Notification
 *
 * Sent to users who favorited an item when its price drops.
 */
class PriceDropNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected CatalogItem $catalogItem,
        protected float $oldPrice,
        protected float $newPrice
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
        $discount = round((($this->oldPrice - $this->newPrice) / $this->oldPrice) * 100);

        return (new MailMessage)
            ->subject(__('notifications.catalog.price_drop_subject', ['product' => $this->catalogItem->name]))
            ->greeting(__('notifications.catalog.price_drop_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.catalog.price_drop_line1', ['product' => $this->catalogItem->name]))
            ->line(__('notifications.catalog.price_drop_line2', [
                'old' => monetaryUnit()->format($this->oldPrice),
                'new' => monetaryUnit()->format($this->newPrice),
                'discount' => $discount,
            ]))
            ->action(__('notifications.catalog.buy_now'), url('/products/' . $this->catalogItem->id))
            ->line(__('notifications.catalog.limited_offer'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'price_drop',
            'catalog_item_id' => $this->catalogItem->id,
            'product_name' => $this->catalogItem->name,
            'old_price' => $this->oldPrice,
            'new_price' => $this->newPrice,
            'discount_percent' => round((($this->oldPrice - $this->newPrice) / $this->oldPrice) * 100),
        ];
    }

    /**
     * Get the catalog item
     */
    public function getCatalogItem(): CatalogItem
    {
        return $this->catalogItem;
    }

    /**
     * Get old price
     */
    public function getOldPrice(): float
    {
        return $this->oldPrice;
    }

    /**
     * Get new price
     */
    public function getNewPrice(): float
    {
        return $this->newPrice;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercent(): float
    {
        return round((($this->oldPrice - $this->newPrice) / $this->oldPrice) * 100, 2);
    }
}
