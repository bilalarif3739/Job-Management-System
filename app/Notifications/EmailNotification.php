<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class EmailNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    private string $token;
    private string $url;
    private int $invite;

    // public function __construct(string $token,string $expires, int $invite)
    // {
    //     $this->token = $token;
    //     $this->expires = $expires;
    //     $this->invite = $invite;
    // }

    public function __construct(string $token, string $url)
    {
        $this->token = $token;
        $this->url = $url;
        // $this->invite = $invite;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // $queryParams = http_build_query([
        //     'token' => $this->token,
        //     // 'expires' => $this->expires,
        //     // 'invite' => $this->invite,
        // ]);

        // $url = url('api/verify-email'). '?' . $queryParams;
        $url = config('app.url') . $this->url . $this->token;
        // verification notification only
        $url = config('app.frontend_url') . '/verify-email?token=' . $this->token;

        return (new MailMessage)
            ->from('chatapp@example.com', 'Whistleit')
            ->subject('Verify Your Email Address - Whistleit')
            ->greeting('Hello!')
            ->line('Thank you for registering with Whistleit!')
            ->line('To complete your registration and activate your account, please verify your email address by clicking the button below.')
            ->action('Verify Email Address', $url)
            ->line('If you did not create an account with Whistleit, you can safely ignore this email.')
            ->salutation('Best Regards, Whistleit Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
