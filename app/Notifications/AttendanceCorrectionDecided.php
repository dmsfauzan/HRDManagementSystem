<?php

namespace App\Notifications;

use App\Models\AttendanceCorrection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceCorrectionDecided extends Notification
{
    use Queueable;

    public function __construct(public AttendanceCorrection $correction) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
