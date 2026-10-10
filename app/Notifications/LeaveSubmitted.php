<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveSubmitted extends Notification
{
    use Queueable;

    public function __construct(public LeaveRequest $leave) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leave_submitted',
            'message' => sprintf(
                'Pengajuan %s dari %s (%s s.d. %s) menunggu persetujuan.',
                $this->leave->leaveType?->name ?? 'cuti',
                $this->leave->employee?->full_name ?? 'Karyawan',
                $this->leave->start_date?->format('d/m/Y'),
                $this->leave->end_date?->format('d/m/Y')
            ),
            'leave_request_id' => $this->leave->id,
        ];
    }
}
