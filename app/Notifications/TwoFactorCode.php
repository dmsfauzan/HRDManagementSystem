<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCode extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'two_factor_code',
            'message' => 'Kode verifikasi login anda telah dikirim.',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Verifikasi Login')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Berikut kode verifikasi login Anda:')
            ->line('**'.$this->code.'**')
            ->line('Kode berlaku selama 10 menit. Abaikan email ini jika Anda tidak merasa melakukan login.');
    }
}
