<?php

namespace App\Services;

use App\Models\ApprovalAction;
use App\Models\ApprovalStep;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OvertimeService
{
    public function submit(Employee $employee, array $data): Overtime
    {
        $date = Carbon::parse($data['date']);
        $start = Carbon::parse($data['date'].' '.$data['start_at']);
        $end = Carbon::parse($data['date'].' '.$data['end_at']);

        if ($end->lessThanOrEqualTo($start)) {
            // Dukung lembur lintas tengah malam.
            $end->addDay();
        }

        $duration = $start->diffInMinutes($end);

        if ($duration < 30) {
            throw ValidationException::withMessages(['end_at' => 'Durasi lembur minimal 30 menit.']);
        }

        if ($duration > 12 * 60) {
            throw ValidationException::withMessages(['end_at' => 'Durasi lembur maksimal 12 jam.']);
        }

        return DB::transaction(function () use ($employee, $data, $date, $duration) {
            $overlap = Overtime::where('employee_id', $employee->id)
                ->whereDate('date', $date->toDateString())
                ->whereIn('status', [Overtime::STATUS_PENDING, Overtime::STATUS_APPROVED])
                ->where(function ($q) use ($data) {
                    $q->where('start_at', '<', $data['end_at'])
                        ->where('end_at', '>', $data['start_at']);
                })
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages(['start_at' => 'Terdapat pengajuan lembur yang tumpang tindih pada waktu tersebut.']);
            }

            $overtime = Overtime::create([
                'employee_id' => $employee->id,
                'date' => $date->toDateString(),
                'start_at' => $data['start_at'],
                'end_at' => $data['end_at'],
                'duration_minutes' => $duration,
                'reason' => $data['reason'],
                'status' => Overtime::STATUS_PENDING,
            ]);

            AuditLogger::log('overtime.submitted', $overtime, [], [
                'date' => $date->toDateString(),
                'duration_minutes' => $duration,
            ]);

            $this->createApprovalSteps($overtime);

            return $overtime;
        });
    }

    /**
     * Bentuk langkah persetujuan lembur: Manager, lalu HRD bila melewati ambang menit.
     */
    private function createApprovalSteps(Overtime $overtime): void
    {
        $levels = ['manager'];

        if ((int) $overtime->duration_minutes >= (int) config('overtime.multi_level_min_minutes', 180)) {
            $levels[] = 'hrd_admin';
        }

        foreach ($levels as $index => $role) {
            ApprovalStep::create([
                'approvable_type' => $overtime->getMorphClass(),
                'approvable_id' => $overtime->id,
                'level' => $index + 1,
                'approver_role' => $role,
                'status' => ApprovalStep::STATUS_PENDING,
            ]);
        }
    }

    public function currentStep(Overtime $overtime): ?ApprovalStep
    {
        return ApprovalStep::where('approvable_type', $overtime->getMorphClass())
            ->where('approvable_id', $overtime->id)
            ->where('status', ApprovalStep::STATUS_PENDING)
            ->orderBy('level')
            ->first();
    }

    public function canDecideStep(User $user, Overtime $overtime): bool
    {
        if ($overtime->status !== Overtime::STATUS_PENDING) {
            return false;
        }

        $step = $this->currentStep($overtime);
        if (! $step) {
            return false;
        }

        if ($step->approver_role === 'hrd_admin') {
            return $user->can('attendance.manage');
        }

        if ($user->can('attendance.manage')) {
            return true;
        }

        return $user->hasRole('manager')
            && $user->employee
            && $overtime->employee?->manager_id === $user->employee->id;
    }

    public function decide(Overtime $overtime, User $approver, string $action, ?string $note = null): Overtime
    {
        if ($overtime->status !== Overtime::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => 'Pengajuan lembur ini sudah diproses.']);
        }

        return DB::transaction(function () use ($overtime, $approver, $action, $note) {
            $overtime = Overtime::whereKey($overtime->id)->lockForUpdate()->firstOrFail();

            if (! $this->canDecideStep($approver, $overtime)) {
                throw ValidationException::withMessages(['status' => 'Anda tidak berwenang atau pengajuan ini sudah diproses.']);
            }

            $step = $this->currentStep($overtime);
            if (! $step) {
                throw ValidationException::withMessages(['status' => 'Tidak ada langkah persetujuan yang menunggu.']);
            }

            $step->update([
                'status' => $action === 'approved' ? ApprovalStep::STATUS_APPROVED : ApprovalStep::STATUS_REJECTED,
                'approver_id' => $approver->id,
                'acted_at' => now(),
                'notes' => $note,
            ]);

            ApprovalAction::create([
                'approvable_type' => $overtime->getMorphClass(),
                'approvable_id' => $overtime->id,
                'action' => $action,
                'actor_id' => $approver->id,
                'notes' => ($note ? $note.' ' : '').'(Level '.$step->level.': '.$step->approver_role.')',
                'acted_at' => now(),
            ]);

            if ($action === 'rejected') {
                $overtime->status = Overtime::STATUS_REJECTED;
                $overtime->approver_id = $approver->id;
                $overtime->decided_at = now();
                $overtime->decision_note = $note;
                $overtime->save();

                ApprovalStep::where('approvable_type', $overtime->getMorphClass())
                    ->where('approvable_id', $overtime->id)
                    ->where('status', ApprovalStep::STATUS_PENDING)
                    ->update(['status' => ApprovalStep::STATUS_REJECTED, 'acted_at' => now()]);
            } else {
                $hasPending = ApprovalStep::where('approvable_type', $overtime->getMorphClass())
                    ->where('approvable_id', $overtime->id)
                    ->where('status', ApprovalStep::STATUS_PENDING)
                    ->exists();

                if (! $hasPending) {
                    $overtime->status = Overtime::STATUS_APPROVED;
                    $overtime->approver_id = $approver->id;
                    $overtime->decided_at = now();
                    $overtime->decision_note = $note;
                    $overtime->save();
                }
            }

            AuditLogger::log('overtime.decided', $overtime, ['status' => 'pending'], [
                'status' => $overtime->status,
                'step_level' => $step->level,
            ]);

            return $overtime;
        });
    }

    public function cancel(Overtime $overtime, User $actor): Overtime
    {
        if (in_array($overtime->status, [Overtime::STATUS_REJECTED, Overtime::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages(['status' => 'Pengajuan yang sudah final tidak dapat dibatalkan.']);
        }

        return DB::transaction(function () use ($overtime, $actor) {
            $overtime = Overtime::whereKey($overtime->id)->lockForUpdate()->firstOrFail();
            $overtime->status = Overtime::STATUS_CANCELLED;
            $overtime->save();

            ApprovalAction::create([
                'approvable_type' => $overtime->getMorphClass(),
                'approvable_id' => $overtime->id,
                'action' => 'cancelled',
                'actor_id' => $actor->id,
                'notes' => 'Pengajuan lembur dibatalkan.',
                'acted_at' => now(),
            ]);

            AuditLogger::log('overtime.cancelled', $overtime, [], ['status' => 'cancelled']);

            return $overtime;
        });
    }
}
