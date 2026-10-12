<?php

namespace App\Notifications;

use App\Models\AttendanceCorrection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceCorrectionSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AttendanceCorrection $correction) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengajuan Koreksi Absensi Menunggu Persetujuan')
            ->greeting('Halo '.$notifiable->name.',')
            ->line(sprintf(
                '%s mengajukan koreksi absensi untuk tanggal %s.',
                $this->correction->employee?->full_name ?? 'Karyawan',
                $this->correction->work_date?->format('d/m/Y')
            ))
            ->line('Alasan: '.$this->correction->reason)
            ->action('Lihat Pengajuan', route('attendance.corrections.index'))
            ->line('Silakan tinjau dan beri keputusan.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'attendance_correction_submitted',
            'message' => sprintf(
                'Pengajuan koreksi absensi dari %s untuk tanggal %s menunggu persetujuan.',
                $this->correction->employee?->full_name ?? 'Karyawan',
                $this->correction->work_date?->format('d/m/Y')
            ),
            'correction_id' => $this->correction->id,
        ];
    }
}
