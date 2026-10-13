<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\ShiftSwap;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftSwapService
{
    public function submit(Employee $requester, array $data): ShiftSwap
    {
        $target = Employee::findOrFail($data['target_employee_id']);

        if ($target->id === $requester->id) {
            throw ValidationException::withMessages(['target_employee_id' => 'Tidak dapat menukar shift dengan diri sendiri.']);
        }

        $date = Carbon::parse($data['date'], tz());

        $swap = ShiftSwap::create([
            'requester_employee_id' => $requester->id,
            'target_employee_id' => $target->id,
            'date' => $date->toDateString(),
            'reason' => $data['reason'],
            'status' => ShiftSwap::STATUS_PENDING,
        ]);

        AuditLogger::log('shift_swap.submitted', $swap, [], [
            'target_employee_id' => $target->id,
            'date' => $date->toDateString(),
        ]);

        return $swap;
    }

    public function decide(ShiftSwap $swap, User $approver, string $action, ?string $note = null): ShiftSwap
    {
        if ($swap->status !== ShiftSwap::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => 'Pengajuan tukar shift ini sudah diproses.']);
        }

        return DB::transaction(function () use ($swap, $approver, $action, $note) {
            $swap = ShiftSwap::whereKey($swap->id)->lockForUpdate()->firstOrFail();

            if ($swap->status !== ShiftSwap::STATUS_PENDING) {
                throw ValidationException::withMessages(['status' => 'Pengajuan tukar shift ini sudah diproses.']);
            }

            $swap->status = $action === 'approved' ? ShiftSwap::STATUS_APPROVED : ShiftSwap::STATUS_REJECTED;
            $swap->approver_id = $approver->id;
            $swap->decided_at = now();
            $swap->decision_note = $note;
            $swap->save();

            if ($swap->status === ShiftSwap::STATUS_APPROVED) {
                $this->applySwap($swap);
            }

            AuditLogger::log('shift_swap.decided', $swap, ['status' => 'pending'], ['status' => $swap->status]);

            return $swap;
        });
    }

    public function cancel(ShiftSwap $swap, User $actor): ShiftSwap
    {
        if ($swap->status !== ShiftSwap::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => 'Pengajuan yang sudah diproses tidak dapat dibatalkan.']);
        }

        $swap->update(['status' => ShiftSwap::STATUS_CANCELLED]);

        AuditLogger::log('shift_swap.cancelled', $swap, [], ['status' => 'cancelled']);

        return $swap;
    }

    /**
     * Menukar jadwal mingguan antara pemohon dan target pada minggu tanggal terkait.
     */
    private function applySwap(ShiftSwap $swap): void
    {
        $weekStart = Carbon::parse($swap->date)->startOfWeek(Carbon::MONDAY)->toDateString();

        $requesterAssignment = ScheduleAssignment::where('employee_id', $swap->requester_employee_id)
            ->whereDate('week_start', $weekStart)->first();
        $targetAssignment = ScheduleAssignment::where('employee_id', $swap->target_employee_id)
            ->whereDate('week_start', $weekStart)->first();

        $requesterSchedule = $requesterAssignment?->work_schedule_id ?? $swap->requester?->work_schedule_id;
        $targetSchedule = $targetAssignment?->work_schedule_id ?? $swap->target?->work_schedule_id;

        if ($requesterSchedule && $targetSchedule) {
            ScheduleAssignment::updateOrCreate(
                ['employee_id' => $swap->requester_employee_id, 'week_start' => $weekStart],
                ['work_schedule_id' => $targetSchedule]
            );
            ScheduleAssignment::updateOrCreate(
                ['employee_id' => $swap->target_employee_id, 'week_start' => $weekStart],
                ['work_schedule_id' => $requesterSchedule]
            );
        }
    }
}
