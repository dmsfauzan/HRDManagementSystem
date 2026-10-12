<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveRequest $leave) {}

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
            ->subject('Pengajuan Cuti Menunggu Persetujuan')
            ->greeting('Halo '.$notifiable->name.',')
            ->line(sprintf(
                '%s (%s) mengajukan %s periode %s s.d. %s.',
                $this->leave->employee?->full_name ?? 'Karyawan',
                $this->leave->employee?->employee_id ?? '-',
                $this->leave->leaveType?->name ?? 'cuti',
                $this->leave->start_date?->format('d/m/Y'),
                $this->leave->end_date?->format('d/m/Y')
            ))
            ->line('Alasan: '.$this->leave->reason)
            ->action('Lihat Pengajuan', route('leave.show', $this->leave))
            ->line('Silakan tinjau dan beri keputusan.');
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
