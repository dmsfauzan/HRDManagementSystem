<?php

namespace App\Notifications;

use App\Models\Overtime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OvertimeSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Overtime $overtime) {}

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
            'type' => 'overtime_submitted',
            'message' => sprintf(
                'Pengajuan lembur %s pada %s (%d menit) menunggu persetujuan.',
                $this->overtime->employee?->full_name ?? 'Karyawan',
                $this->overtime->date?->format('d/m/Y'),
                $this->overtime->duration_minutes
            ),
            'overtime_id' => $this->overtime->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengajuan Lembur Menunggu Persetujuan')
            ->greeting('Halo '.$notifiable->name.',')
            ->line(sprintf(
                '%s mengajukan lembur pada %s pukul %s - %s (%d menit).',
                $this->overtime->employee?->full_name ?? 'Karyawan',
                $this->overtime->date?->format('d/m/Y'),
                $this->overtime->startLabel(),
                $this->overtime->endLabel(),
                $this->overtime->duration_minutes
            ))
            ->line('Alasan: '.$this->overtime->reason)
            ->action('Tinjau Pengajuan', route('overtime.index'))
            ->line('Silakan beri keputusan.');
    }
}
