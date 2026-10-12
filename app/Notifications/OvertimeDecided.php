<?php

namespace App\Notifications;

use App\Models\Overtime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OvertimeDecided extends Notification implements ShouldQueue
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
        $status = $this->overtime->status === Overtime::STATUS_APPROVED ? 'disetujui' : 'ditolak';

        return [
            'type' => 'overtime_decided',
            'message' => sprintf(
                'Pengajuan lembur tanggal %s Anda %s.',
                $this->overtime->date?->format('d/m/Y'),
                $status
            ),
            'overtime_id' => $this->overtime->id,
            'status' => $this->overtime->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->overtime->status === Overtime::STATUS_APPROVED ? 'disetujui' : 'ditolak';

        return (new MailMessage)
            ->subject('Pengajuan Lembur '.$status)
            ->greeting('Halo '.$notifiable->name.',')
            ->line(sprintf('Pengajuan lembur tanggal %s Anda telah %s.', $this->overtime->date?->format('d/m/Y'), $status))
            ->action('Lihat Lembur', route('overtime.index'));
    }
}
