<?php

namespace App\Domain\Accounting\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Accounting\Models\Withdraw;

/**
 * Withdraw Requested Notification
 *
 * Sent to merchant when withdrawal request is submitted.
 */
class WithdrawRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Withdraw $withdraw
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
            ->subject(__('notifications.accounting.withdraw_requested_subject'))
            ->greeting(__('notifications.accounting.withdraw_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.accounting.withdraw_requested_line1', [
                'amount' => monetaryUnit()->format($this->withdraw->amount),
            ]))
            ->line(__('notifications.accounting.withdraw_reference', [
                'reference' => $this->withdraw->reference,
            ]))
            ->line(__('notifications.accounting.withdraw_processing_time'))
            ->action(__('notifications.accounting.view_withdrawals'), url('/merchant/withdrawals'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'withdraw_requested',
            'withdraw_id' => $this->withdraw->id,
            'reference' => $this->withdraw->reference,
            'amount' => $this->withdraw->amount,
            'status' => $this->withdraw->status,
        ];
    }

    /**
     * Get the withdraw
     */
    public function getWithdraw(): Withdraw
    {
        return $this->withdraw;
    }
}
