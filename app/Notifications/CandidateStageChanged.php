<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CandidateStageChanged extends Notification implements ShouldQueue
{
    use Queueable;

    private const LABELS = [
        'screening' => 'sedang ditinjau',
        'interview' => 'diundang wawancara',
        'offer' => 'menerima penawaran',
        'hired' => 'diterima',
        'rejected' => 'belum dapat kami proses',
        'withdrawn' => 'dibatalkan',
    ];

    public function __construct(public JobApplication $application, public string $toStatus) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = self::LABELS[$this->toStatus] ?? $this->toStatus;

        return (new MailMessage)
            ->subject('Status Lamaran Anda: '.$label)
            ->greeting('Halo '.$this->application->candidate_name.',')
            ->line(sprintf('Lamaran Anda untuk posisi %s saat ini %s.', $this->application->jobPosting?->title ?? '-', $label))
            ->line('Terima kasih atas ketertarikan Anda.');
    }
}
