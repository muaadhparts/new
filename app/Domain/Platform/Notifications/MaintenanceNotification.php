<?php

namespace App\Domain\Platform\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

/**
 * Maintenance Notification
 *
 * Sent to users about scheduled maintenance.
 */
class MaintenanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Carbon $startTime,
        protected Carbon $endTime,
        protected ?string $reason = null
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
        $mail = (new MailMessage)
            ->subject(__('notifications.platform.maintenance_subject'))
            ->greeting(__('notifications.platform.maintenance_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.platform.maintenance_scheduled'))
            ->line(__('notifications.platform.maintenance_start', [
                'time' => $this->startTime->format('Y-m-d H:i'),
            ]))
            ->line(__('notifications.platform.maintenance_end', [
                'time' => $this->endTime->format('Y-m-d H:i'),
            ]));

        if ($this->reason) {
            $mail->line(__('notifications.platform.maintenance_reason', [
                'reason' => $this->reason,
            ]));
        }

        return $mail->line(__('notifications.platform.maintenance_apology'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'maintenance',
            'start_time' => $this->startTime->toIso8601String(),
            'end_time' => $this->endTime->toIso8601String(),
            'reason' => $this->reason,
        ];
    }

    /**
     * Get the start time
     */
    public function getStartTime(): Carbon
    {
        return $this->startTime;
    }

    /**
     * Get the end time
     */
    public function getEndTime(): Carbon
    {
        return $this->endTime;
    }
}
