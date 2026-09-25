<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SendOtpNotification extends Notification
{
    use Queueable;

    public $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('BFAD 178 - Password Reset OTP')
            ->greeting('Hello Responder,')
            ->line('Your one-time verification code is:')
            ->line('**' . $this->otp . '**')
            ->line('This code will expire in 10 minutes.')
            ->line('If you did not request this, please ignore this email.');
    }
}