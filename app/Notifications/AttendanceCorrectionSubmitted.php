<?php

namespace App\Notifications;

use App\Models\AttendanceCorrection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceCorrectionSubmitted extends Notification
{
    use Queueable;

    public function __construct(public AttendanceCorrection $correction) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
