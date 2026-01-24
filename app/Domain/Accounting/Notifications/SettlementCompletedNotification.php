<?php

namespace App\Domain\Accounting\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Settlement Completed Notification
 *
 * Sent to merchant when settlement batch is processed.
 */
class SettlementCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected float $amount,
        protected int $ordersCount,
        protected string $period
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
            ->subject(__('notifications.accounting.settlement_subject', ['period' => $this->period]))
            ->greeting(__('notifications.accounting.settlement_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.accounting.settlement_completed'))
            ->line(__('notifications.accounting.settlement_amount', [
                'amount' => monetaryUnit()->format($this->amount),
            ]))
            ->line(__('notifications.accounting.settlement_orders', [
                'count' => $this->ordersCount,
            ]))
            ->action(__('notifications.accounting.view_statements'), url('/merchant/statements'))
            ->line(__('notifications.order.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'settlement_completed',
            'amount' => $this->amount,
            'orders_count' => $this->ordersCount,
            'period' => $this->period,
        ];
    }

    /**
     * Get the settlement amount
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get the orders count
     */
    public function getOrdersCount(): int
    {
        return $this->ordersCount;
    }
}
