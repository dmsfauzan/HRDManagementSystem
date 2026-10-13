<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobApplication $application) {}

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
            'type' => 'application_received',
            'message' => sprintf(
                'Lamaran baru: %s untuk posisi %s.',
                $this->application->candidate_name,
                $this->application->jobPosting?->title ?? '-'
            ),
            'application_id' => $this->application->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Lamaran Baru Masuk')
            ->greeting('Halo '.$notifiable->name.',')
            ->line(sprintf(
                '%s melamar posisi %s.',
                $this->application->candidate_name,
                $this->application->jobPosting?->title ?? '-'
            ))
            ->action('Lihat Pelamar', route('recruitment.applications.index'))
            ->line('Silakan tinjau kandidat di pipeline rekrutmen.');
    }
}
