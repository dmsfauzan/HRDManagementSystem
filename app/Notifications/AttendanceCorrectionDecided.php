<?php

namespace App\Notifications;

use App\Models\AttendanceCorrection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceCorrectionDecided extends Notification implements ShouldQueue
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
        $status = $this->correction->status === 'approved' ? 'disetujui' : 'ditolak';

        $mail = (new MailMessage)
            ->subject('Pengajuan Koreksi Absensi '.$status)
            ->greeting('Halo '.$notifiable->name.',')
            ->line(sprintf(
                'Pengajuan koreksi absensi tanggal %s Anda telah %s.',
                $this->correction->work_date?->format('d/m/Y'),
                $status
            ));

        if ($this->correction->decision_note) {
            $mail->line('Catatan: '.$this->correction->decision_note);
        }

        return $mail->action('Lihat Absensi', route('attendance.index'));
    }

    public function toArray(object $notifiable): array
    {
        $status = $this->correction->status === 'approved' ? 'disetujui' : 'ditolak';

        return [
            'type' => 'attendance_correction_decided',
            'message' => sprintf(
                'Pengajuan koreksi absensi tanggal %s Anda %s.',
                $this->correction->work_date?->format('d/m/Y'),
                $status
            ),
            'correction_id' => $this->correction->id,
            'status' => $this->correction->status,
        ];
    }
}
