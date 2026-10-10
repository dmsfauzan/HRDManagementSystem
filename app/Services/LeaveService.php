<?php

namespace App\Services;

use App\Models\ApprovalAction;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function ensureBalances(Employee $employee, ?int $year = null): void
    {
        $year ??= (int) now('Asia/Jakarta')->format('Y');

        LeaveType::where('is_active', true)->where('tracks_balance', true)->get()->each(function (LeaveType $type) use ($employee, $year) {
            LeaveBalance::firstOrCreate(
                ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year],
                ['balance' => $type->default_quota, 'used' => 0]
            );
        });
    }

    /**
     * Hitung jumlah hari cuti berdasarkan kalender kerja (jadwal + libur).
     */
    public function calculateWorkingDays(Employee $employee, Carbon $start, Carbon $end): float
    {
        $schedule = $employee->workSchedule;
        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        $days = 0.0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $isWorkDay = $schedule
                ? $schedule->isWorkDay((int) $cursor->dayOfWeekIso)
                : $cursor->isWeekday();

            if ($isWorkDay && ! in_array($cursor->toDateString(), $holidays, true)) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }

    public function submit(Employee $employee, array $data, ?UploadedFile $attachment = null): LeaveRequest
    {
        $type = LeaveType::findOrFail($data['leave_type_id']);
        $start = Carbon::parse($data['start_date'], 'Asia/Jakarta');
        $end = Carbon::parse($data['end_date'], 'Asia/Jakarta');

        if ($end->lt($start)) {
            throw ValidationException::withMessages(['end_date' => 'Tanggal selesai tidak boleh mendahului tanggal mulai.']);
        }

        if ($type->requires_attachment && ! $attachment) {
            throw ValidationException::withMessages(['attachment' => 'Jenis cuti ini wajib melampirkan dokumen.']);
        }

        $totalDays = $this->calculateWorkingDays($employee, $start, $end);

        if ($totalDays < 1) {
            throw ValidationException::withMessages(['start_date' => 'Rentang tanggal tidak memuat hari kerja.']);
        }

        return DB::transaction(function () use ($employee, $type, $data, $start, $end, $totalDays, $attachment) {
            if ($type->tracks_balance) {
                $balance = $this->lockedBalance($employee, $type, (int) $start->format('Y'));

                if ($balance->balance < $totalDays) {
                    throw ValidationException::withMessages([
                        'leave_type_id' => "Saldo cuti tidak mencukupi. Sisa {$balance->balance} hari, diminta {$totalDays} hari.",
                    ]);
                }
            }

            $request = LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'total_days' => $totalDays,
                'reason' => $data['reason'],
                'status' => LeaveRequest::STATUS_PENDING,
                'requested_at' => now(),
            ]);

            if ($attachment) {
                $request->attachment_path = $this->storeAttachment($request, $attachment);
                $request->save();
            }

            AuditLogger::log('leave.submitted', $request, [], [
                'employee_id' => $request->employee_id,
                'leave_type_id' => $request->leave_type_id,
                'start_date' => $request->start_date?->toDateString(),
                'end_date' => $request->end_date?->toDateString(),
                'total_days' => $request->total_days,
            ]);

            return $request;
        });
    }

    public function decide(LeaveRequest $request, User $approver, string $action, ?string $note = null): LeaveRequest
    {
        if ($request->status !== LeaveRequest::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => 'Pengajuan ini sudah diproses dan tidak dapat diubah.']);
        }

        return DB::transaction(function () use ($request, $approver, $action, $note) {
            $request = LeaveRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($request->status !== LeaveRequest::STATUS_PENDING) {
                throw ValidationException::withMessages(['status' => 'Pengajuan ini sudah diproses.']);
            }

            $request->status = $action === 'approved' ? LeaveRequest::STATUS_APPROVED : LeaveRequest::STATUS_REJECTED;
            $request->approver_id = $approver->id;
            $request->decided_at = now();
            $request->decision_note = $note;
            $request->save();

            ApprovalAction::create([
                'approvable_type' => $request->getMorphClass(),
                'approvable_id' => $request->id,
                'action' => $action,
                'actor_id' => $approver->id,
                'notes' => $note,
                'acted_at' => now(),
            ]);

            if ($request->status === LeaveRequest::STATUS_APPROVED && $request->leaveType?->tracks_balance) {
                $this->debit($request, $approver, 'Persetujuan cuti '.$request->leaveType->name);
            }

            AuditLogger::log('leave.decided', $request, ['status' => 'pending'], [
                'status' => $request->status,
                'approver_id' => $approver->id,
            ]);

            return $request;
        });
    }

    public function cancel(LeaveRequest $request, User $actor): LeaveRequest
    {
        if (in_array($request->status, [LeaveRequest::STATUS_REJECTED, LeaveRequest::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages(['status' => 'Pengajuan yang sudah final tidak dapat dibatalkan.']);
        }

        return DB::transaction(function () use ($request, $actor) {
            $request = LeaveRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($request->status === LeaveRequest::STATUS_APPROVED && $request->leaveType?->tracks_balance) {
                $this->refund($request, $actor, 'Pembatalan cuti '.$request->leaveType->name);
            }

            $request->status = LeaveRequest::STATUS_CANCELLED;
            $request->save();

            ApprovalAction::create([
                'approvable_type' => $request->getMorphClass(),
                'approvable_id' => $request->id,
                'action' => 'cancelled',
                'actor_id' => $actor->id,
                'notes' => 'Pengajuan dibatalkan.',
                'acted_at' => now(),
            ]);

            AuditLogger::log('leave.cancelled', $request, [], ['status' => 'cancelled']);

            return $request;
        });
    }

    public function adjustBalance(LeaveBalance $balance, float $newBalance, User $actor, string $description): LeaveBalance
    {
        return DB::transaction(function () use ($balance, $newBalance, $actor, $description) {
            $balance = LeaveBalance::whereKey($balance->id)->lockForUpdate()->firstOrFail();
            $delta = $newBalance - (float) $balance->balance;
            $balance->balance = $newBalance;
            $balance->save();

            LeaveBalanceTransaction::create([
                'leave_balance_id' => $balance->id,
                'type' => 'adjustment',
                'amount' => $delta,
                'balance_after' => $balance->balance,
                'description' => $description,
                'created_by' => $actor->id,
            ]);

            AuditLogger::log('leave.balance_adjusted', $balance, ['balance' => $balance->balance - $delta], ['balance' => $balance->balance]);

            return $balance;
        });
    }

    private function lockedBalance(Employee $employee, LeaveType $type, int $year): LeaveBalance
    {
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $type->id)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if (! $balance) {
            $balance = LeaveBalance::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'year' => $year,
                'balance' => $type->default_quota,
                'used' => 0,
            ]);
        }

        return $balance;
    }

    private function debit(LeaveRequest $request, User $actor, string $description): void
    {
        $year = (int) $request->start_date->format('Y');
        $balance = $this->lockedBalance($request->employee, $request->leaveType, $year);

        $balance->balance = (float) $balance->balance - (float) $request->total_days;
        $balance->used = (float) $balance->used + (float) $request->total_days;
        $balance->save();

        LeaveBalanceTransaction::create([
            'leave_balance_id' => $balance->id,
            'leave_request_id' => $request->id,
            'type' => 'debit',
            'amount' => -1 * (float) $request->total_days,
            'balance_after' => $balance->balance,
            'description' => $description,
            'created_by' => $actor->id,
        ]);
    }

    private function refund(LeaveRequest $request, User $actor, string $description): void
    {
        $year = (int) $request->start_date->format('Y');
        $balance = $this->lockedBalance($request->employee, $request->leaveType, $year);

        $balance->balance = (float) $balance->balance + (float) $request->total_days;
        $balance->used = max(0, (float) $balance->used - (float) $request->total_days);
        $balance->save();

        LeaveBalanceTransaction::create([
            'leave_balance_id' => $balance->id,
            'leave_request_id' => $request->id,
            'type' => 'credit',
            'amount' => (float) $request->total_days,
            'balance_after' => $balance->balance,
            'description' => $description,
            'created_by' => $actor->id,
        ]);
    }

    public function storeAttachment(LeaveRequest $request, UploadedFile $file): string
    {
        $ext = $file->getClientOriginalExtension() ?: 'pdf';
        $path = sprintf('leave/%d/attachment.%s', $request->id, $ext);
        Storage::disk('private')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }
}
