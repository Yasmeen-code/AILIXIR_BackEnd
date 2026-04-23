<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $otp;
    protected $type;

    public function __construct(int $otp, string $type)
    {
        $this->otp = $otp;
        $this->type = $type;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->type) {
            'password_reset' => 'Reset Your Password - OTP Code',
            'email_verification' => 'Verify Your Email - OTP Code',
            default => 'Your OTP Code'
        };

        $message = match ($this->type) {
            'password_reset' => 'Use this code to reset your password.',
            'email_verification' => 'Use this code to verify your email address.',
            default => 'Your verification code is:'
        };

        return (new MailMessage)
            ->subject($subject . ' - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($message)
            ->line('**' . $this->otp . '**')
            ->line('This code expires in 5 minutes.')
            ->line('If you did not request this, please ignore this email.')
            ->salutation('Regards, ' . config('app.name'));
    }
}
