<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecideAttendanceCorrectionRequest;
use App\Http\Requests\StoreAttendanceCorrectionRequest;
use App\Models\ApprovalAction;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Notifications\AttendanceCorrectionDecided;
use App\Notifications\AttendanceCorrectionSubmitted;
use App\Services\AttendanceService;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class AttendanceCorrectionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly AttendanceService $service) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->authorize('viewAny', AttendanceCorrection::class);

        $query = AttendanceCorrection::with(['employee', 'approver']);

        if ($user->can('attendance.manage')) {
            // HRD/Super Admin: semua
        } elseif ($user->hasRole('manager') && $user->employee) {
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $user->employee->id));
        } else {
            $query->where('employee_id', $user->employee?->id ?? 0);
        }

        $corrections = $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('attendance.corrections.index', compact('corrections'));
    }

    public function create(): View
    {
        $this->authorize('create', AttendanceCorrection::class);

        return view('attendance.corrections.create');
    }

    public function store(StoreAttendanceCorrectionRequest $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        $data = $request->validated();

        $correction = DB::transaction(function () use ($employee, $data) {
            $record = AttendanceRecord::where('employee_id', $employee->id)
                ->whereDate('work_date', $data['work_date'])
                ->first();

            return AttendanceCorrection::create([
                'attendance_record_id' => $record?->id,
                'employee_id' => $employee->id,
                'work_date' => $data['work_date'],
                'requested_check_in_at' => $this->combine($data['work_date'], $data['requested_check_in_at'] ?? null),
                'requested_check_out_at' => $this->combine($data['work_date'], $data['requested_check_out_at'] ?? null),
                'reason' => $data['reason'],
                'status' => 'pending',
            ]);
        });

        $this->notifyApprovers($correction);

        return redirect()->route('attendance.corrections.index')
            ->with('success', 'Pengajuan koreksi absensi berhasil dikirim dan menunggu persetujuan.');
    }

    public function decide(DecideAttendanceCorrectionRequest $request, AttendanceCorrection $correction): RedirectResponse
    {
        if ($correction->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses dan tidak dapat diubah.');
        }

        $user = $request->user();

        DB::transaction(function () use ($request, $correction, $user) {
            $correction->update([
                'status' => $request->string('action')->toString(),
                'approved_by' => $user->id,
                'decided_at' => now(),
                'decision_note' => $request->input('decision_note'),
            ]);

            ApprovalAction::create([
                'approvable_type' => $correction->getMorphClass(),
                'approvable_id' => $correction->id,
                'action' => $request->string('action')->toString(),
                'actor_id' => $user->id,
                'notes' => $request->input('decision_note'),
                'acted_at' => now(),
            ]);

            if ($correction->status === 'approved') {
                $record = $correction->attendanceRecord
                    ?? AttendanceRecord::firstOrCreate(
                        ['employee_id' => $correction->employee_id, 'work_date' => $correction->work_date],
                        ['status' => 'absent']
                    );

                $this->service->applyCorrection($record, $correction->fresh(['employee.workSchedule']));
            }
        });

        $correction->refresh();

        AuditLogger::log('attendance.correction_decided', $correction, ['status' => 'pending'], [
            'status' => $correction->status,
            'approved_by' => $correction->approved_by,
        ]);

        if ($correction->employee?->user) {
            $correction->employee->user->notify(new AttendanceCorrectionDecided($correction));
        }

        return redirect()->route('attendance.corrections.index')
            ->with('success', 'Keputusan koreksi absensi berhasil disimpan.');
    }

    private function combine(string $workDate, ?string $time): ?string
    {
        if (! $time) {
            return null;
        }

        return Carbon::parse($time, tz())->toDateTimeString();
    }

    private function notifyApprovers(AttendanceCorrection $correction): void
    {
        $recipients = User::permission('attendance.correction_approve')->get();

        $managerUser = $correction->employee?->manager?->user;
        if ($managerUser && ! $recipients->contains('id', $managerUser->id)) {
            $recipients->push($managerUser);
        }

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AttendanceCorrectionSubmitted($correction));
        }
    }
}
