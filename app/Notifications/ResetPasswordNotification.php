<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;

class ResetPasswordNotification extends ResetPassword
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token, public string $email)
    {
        $this->token = $token;
    }

    /**
     * @param $notifiable
     * @return string
     */
    public function resetUrl($notifiable): string
    {
        return config('app.frontend_url') . 'login/reset-user?token=' . $this->token
            . "&email=" . $notifiable->getAttribute('email');
    }
}
