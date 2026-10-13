<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Employee $employee, public int $daysLeft) {}

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
            'type' => 'contract_expiring',
            'message' => sprintf(
                'Kontrak %s (%s) berakhir dalam %d hari (%s).',
                $this->employee->full_name,
                $this->employee->employee_id,
                $this->daysLeft,
                $this->employee->contract_end_date?->format('d/m/Y')
            ),
            'employee_id' => $this->employee->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kontrak Karyawan Akan Berakhir')
            ->greeting('Halo '.$notifiable->name.',')
            ->line(sprintf(
                'Kontrak %s (%s) akan berakhir pada %s (%d hari lagi).',
                $this->employee->full_name,
                $this->employee->employee_id,
                $this->employee->contract_end_date?->format('d/m/Y'),
                $this->daysLeft
            ))
            ->action('Lihat Karyawan', route('employees.show', $this->employee))
            ->line('Silakan tindak lanjuti perpanjangan atau proses offboarding.');
    }
}
