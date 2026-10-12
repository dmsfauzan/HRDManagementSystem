<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveDecided extends Notification implements ShouldQueue
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
        $status = match ($this->leave->status) {
            'approved' => 'disetujui',
            'rejected' => 'ditolak',
            'cancelled' => 'dibatalkan',
            default => 'diproses',
        };

        $mail = (new MailMessage)
            ->subject('Pengajuan Cuti Anda '.$status)
            ->greeting('Halo '.$notifiable->name.',')
            ->line(sprintf(
                'Pengajuan %s Anda periode %s s.d. %s telah %s.',
                $this->leave->leaveType?->name ?? 'cuti',
                $this->leave->start_date?->format('d/m/Y'),
                $this->leave->end_date?->format('d/m/Y'),
                $status
            ));

        if ($this->leave->decision_note) {
            $mail->line('Catatan: '.$this->leave->decision_note);
        }

        return $mail
            ->action('Lihat Detail', route('leave.show', $this->leave))
            ->line('Terima kasih.');
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
