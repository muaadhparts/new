<?php

namespace App\Domain\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Password Changed Notification
 *
 * Sent to user when their password is changed.
 */
class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ?string $ipAddress = null,
        protected ?string $userAgent = null
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
        $message = (new MailMessage)
            ->subject(__('notifications.identity.password_changed_subject'))
            ->greeting(__('notifications.identity.password_changed_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.identity.password_changed_line1'))
            ->line(__('notifications.identity.password_changed_time', ['time' => now()->format('Y-m-d H:i:s')]));

        if ($this->ipAddress) {
            $message->line(__('notifications.identity.password_changed_ip', ['ip' => $this->ipAddress]));
        }

        return $message
            ->line(__('notifications.identity.password_changed_warning'))
            ->action(__('notifications.identity.contact_support'), url('/support'))
            ->line(__('notifications.identity.password_changed_ignore'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_changed',
            'ip_address' => $this->ipAddress,
            'changed_at' => now()->toISOString(),
        ];
    }

    /**
     * Get IP address
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * Get user agent
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }
}
