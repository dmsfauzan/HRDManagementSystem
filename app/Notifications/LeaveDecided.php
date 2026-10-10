<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveDecided extends Notification
{
    use Queueable;

    public function __construct(public LeaveRequest $leave) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $status = match ($this->leave->status) {
            'approved' => 'disetujui',
            'rejected' => 'ditolak',
            'cancelled' => 'dibatalkan',
            default => 'diproses',
        };

        return [
            'type' => 'leave_decided',
            'message' => sprintf(
                'Pengajuan %s Anda (%s s.d. %s) %s.',
                $this->leave->leaveType?->name ?? 'cuti',
                $this->leave->start_date?->format('d/m/Y'),
                $this->leave->end_date?->format('d/m/Y'),
                $status
            ),
            'leave_request_id' => $this->leave->id,
            'status' => $this->leave->status,
        ];
    }
}
