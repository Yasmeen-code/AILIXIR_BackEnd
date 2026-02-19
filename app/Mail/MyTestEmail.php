<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable  // ← غيرتي الاسم لـ OtpMail
{
    use Queueable, SerializesModels;

    public $otp;           // ← أضيفي الـ OTP
    public $type;          // ← نوع الـ OTP (email_verification أو password_reset)

    /**
     * Create a new message instance.
     */
    public function __construct($otp, $type = 'email_verification')
    {
        $this->otp = $otp;
        $this->type = $type;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->type === 'password_reset'
            ? 'Password Reset OTP'
            : 'Email Verification OTP';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',  // ← هننشئ الـ View ده
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
