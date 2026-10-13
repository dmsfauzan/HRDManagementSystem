<?php

namespace App\Console\Commands;

use App\Enums\EmploymentStatus;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\ContractExpiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class RemindContractExpiry extends Command
{
    protected $signature = 'contracts:remind {--days=30 : Ambang hari sebelum kontrak berakhir}';

    protected $description = 'Mengirim pengingat kontrak karyawan yang akan berakhir (ke HRD & atasan).';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $today = tz_today();
        $limit = $today->copy()->addDays($days);

        $hrdRecipients = User::permission('employees.manage')->get();
        $sent = 0;

        Employee::where('employment_status', EmploymentStatus::Active->value)
            ->whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '>=', $today->toDateString())
            ->whereDate('contract_end_date', '<=', $limit->toDateString())
            ->get()
            ->each(function (Employee $employee) use ($hrdRecipients, $today, &$sent) {
                $daysLeft = (int) $today->diffInDays($employee->contract_end_date);

                $recipients = $hrdRecipients->collect();
                $managerUser = $employee->manager?->user;
                if ($managerUser && ! $recipients->contains('id', $managerUser->id)) {
                    $recipients->push($managerUser);
                }

                if ($recipients->isNotEmpty()) {
                    Notification::send($recipients, new ContractExpiring($employee, $daysLeft));
                }

                $employee->contract_reminded_at = now();
                $employee->save();
                $sent++;
            });

        $this->info("Pengingat kontrak: {$sent} karyawan diproses.");

        return self::SUCCESS;
    }
}
