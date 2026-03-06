<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SendOtpNotification extends Notification
{
    protected int $otp;
    protected string $type;

    public function __construct(int $otp, string $type)
    {
        $this->otp = $otp;
        $this->type = $type;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $subject = $this->type === 'email_verification'
            ? 'Email Verification OTP'
            : 'Password Reset OTP';

        return (new MailMessage)
            ->subject($subject)
            ->line("Your OTP code is: {$this->otp}")
            ->line('This OTP will expire in 15 minutes.')
            ->line('If you did not request this, ignore this email.');
    }
}
