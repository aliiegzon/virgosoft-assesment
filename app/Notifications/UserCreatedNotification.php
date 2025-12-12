<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification
{
    /**
     * @param $token
     */
    public function __construct(private $token)
    {
    }

    /**
     * @param $notifiable
     * @return string[]
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('User Created')
            ->markdown('mail.user-created', [
                'name' => $notifiable->getAttribute('name'),
                'url' => config('app.frontend_url') . 'login/new-user?token=' . $this->token
                    . "&email=" . $notifiable->getAttribute('email')
            ]);
    }
}
