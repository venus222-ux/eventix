<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail']; // or ['mail', 'database'] if you want
    }

    public function toMail($notifiable)
    {
        $url = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/')."/reset-password/{$this->token}?email={$notifiable->email}";

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->greeting("Hello {$notifiable->name},")
            ->line('You requested a password reset.')
            ->action('Reset Password', $url)
            ->line('This link will expire in 60 minutes.')
            ->line('If you didn’t request this, ignore this email.');
    }
}
