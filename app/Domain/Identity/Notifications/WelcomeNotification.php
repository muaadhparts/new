<?php

namespace App\Domain\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Welcome Notification
 *
 * Sent to user after successful registration.
 */
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ?string $role = 'user'
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
            ->subject(__('notifications.identity.welcome_subject'))
            ->greeting(__('notifications.identity.welcome_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.identity.welcome_line1'));

        if ($this->role === 'merchant') {
            $message->line(__('notifications.identity.merchant_welcome'))
                ->action(__('notifications.identity.setup_store'), url('/merchant/setup'));
        } else {
            $message->line(__('notifications.identity.user_welcome'))
                ->action(__('notifications.identity.start_shopping'), url('/'));
        }

        return $message->line(__('notifications.identity.welcome_closing'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'role' => $this->role,
            'registered_at' => now()->toISOString(),
        ];
    }

    /**
     * Get the role
     */
    public function getRole(): string
    {
        return $this->role ?? 'user';
    }
}
