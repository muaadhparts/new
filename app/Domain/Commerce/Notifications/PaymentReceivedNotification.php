<?php

namespace App\Domain\Commerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Commerce\Models\Purchase;

/**
 * Payment Received Notification
 *
 * Sent to customer when payment is confirmed.
 */
class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Purchase $purchase,
        protected float $amount,
        protected string $paymentMethod
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
            ->subject(__('notifications.payment.received_subject', ['order' => $this->purchase->order_number]))
            ->greeting(__('notifications.payment.received_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.payment.received_line1', [
                'amount' => monetaryUnit()->format($this->amount),
                'order' => $this->purchase->order_number,
            ]))
            ->line(__('notifications.payment.received_line2', ['method' => $this->paymentMethod]))
            ->action(__('notifications.order.view_order'), url('/purchases/' . $this->purchase->id))
            ->line(__('notifications.order.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'purchase_id' => $this->purchase->id,
            'order_number' => $this->purchase->order_number,
            'amount' => $this->amount,
            'payment_method' => $this->paymentMethod,
        ];
    }

    /**
     * Get the purchase
     */
    public function getPurchase(): Purchase
    {
        return $this->purchase;
    }

    /**
     * Get the amount
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get the payment method
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }
}
