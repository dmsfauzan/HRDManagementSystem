<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\WorkSchedule;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RosterController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('attendance.manage'), 403);

        $weekStart = $this->weekStart($request->input('week'));

        $employees = Employee::with(['department', 'position', 'workSchedule'])
            ->where('employment_status', 'active')
            ->orderBy('full_name')
            ->get();

        $assignments = ScheduleAssignment::with('workSchedule')
            ->whereDate('week_start', $weekStart->toDateString())
            ->get()
            ->keyBy('employee_id');

        $schedules = WorkSchedule::where('is_active', true)->orderBy('name')->get();

        return view('roster.index', compact('employees', 'assignments', 'schedules', 'weekStart'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('attendance.manage'), 403);

        $data = $request->validate([
            'week_start' => ['required', 'date'],
            'assignments' => ['required', 'array'],
            'assignments.*' => ['nullable', 'exists:work_schedules,id'],
        ], [
            'assignments.required' => 'Data penjadwalan kosong.',
        ]);

        $weekStart = $this->weekStart($data['week_start'])->toDateString();

        foreach ($data['assignments'] as $employeeId => $scheduleId) {
            $employee = Employee::find($employeeId);
            if (! $employee) {
                continue;
            }

            if (! $scheduleId) {
                ScheduleAssignment::where('employee_id', $employee->id)->whereDate('week_start', $weekStart)->delete();

                continue;
            }

            ScheduleAssignment::updateOrCreate(
                ['employee_id' => $employee->id, 'week_start' => $weekStart],
                ['work_schedule_id' => $scheduleId, 'created_by' => $request->user()->id]
            );
        }

        AuditLogger::log('roster.updated', null, [], ['week_start' => $weekStart]);

        return redirect()->route('roster.index', ['week' => $weekStart])
            ->with('success', 'Penjadwalan mingguan berhasil disimpan.');
    }

    private function weekStart(?string $value): Carbon
    {
        $base = $value ? Carbon::parse($value, tz()) : tz_today();

        return $base->startOfWeek(Carbon::MONDAY)->startOfDay();
    }
}
