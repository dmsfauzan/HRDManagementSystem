<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkScheduleRequest;
use App\Http\Requests\UpdateWorkScheduleRequest;
use App\Models\WorkSchedule;
use App\Services\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkScheduleController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        abort_unless(auth()->user()->can('attendance.manage'), 403);

        $schedules = WorkSchedule::withCount('employees')->orderBy('name')->paginate(20);

        return view('work-schedules.index', compact('schedules'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->can('attendance.manage'), 403);

        return view('work-schedules.create');
    }

    public function store(StoreWorkScheduleRequest $request): RedirectResponse
    {
        $schedule = WorkSchedule::create($this->payload($request));
        AuditLogger::log('work_schedule.created', $schedule, [], ['code' => $schedule->code, 'name' => $schedule->name]);

        return redirect()->route('work-schedules.index')->with('success', 'Jadwal kerja berhasil ditambahkan.');
    }

    public function edit(WorkSchedule $workSchedule): View
    {
        abort_unless(auth()->user()->can('attendance.manage'), 403);

        return view('work-schedules.edit', ['schedule' => $workSchedule]);
    }

    public function update(UpdateWorkScheduleRequest $request, WorkSchedule $workSchedule): RedirectResponse
    {
        $workSchedule->update($this->payload($request));
        AuditLogger::log('work_schedule.updated', $workSchedule, [], ['code' => $workSchedule->code, 'name' => $workSchedule->name]);

        return redirect()->route('work-schedules.index')->with('success', 'Jadwal kerja berhasil diperbarui.');
    }

    public function destroy(WorkSchedule $workSchedule): RedirectResponse
    {
        abort_unless(auth()->user()->can('attendance.manage'), 403);

        if ($workSchedule->employees()->exists()) {
            return back()->with('error', 'Jadwal tidak dapat dihapus karena masih dipakai karyawan.');
        }

        AuditLogger::log('work_schedule.deleted', $workSchedule, ['code' => $workSchedule->code], []);
        $workSchedule->delete();

        return redirect()->route('work-schedules.index')->with('success', 'Jadwal kerja berhasil dihapus.');
    }

    private function payload($request): array
    {
        return [
            'code' => $request->string('code'),
            'name' => $request->string('name'),
            'check_in_time' => substr($request->string('check_in_time')->toString(), 0, 5).':00',
            'check_out_time' => substr($request->string('check_out_time')->toString(), 0, 5).':00',
            'late_tolerance_minutes' => $request->integer('late_tolerance_minutes'),
            'work_days' => array_map('intval', $request->input('work_days', [])),
            'crosses_midnight' => $request->boolean('crosses_midnight', false),
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
