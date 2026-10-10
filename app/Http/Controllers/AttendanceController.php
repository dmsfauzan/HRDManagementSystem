<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly AttendanceService $service) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->authorize('viewAny', AttendanceRecord::class);

        $todayStr = Carbon::today(AttendanceService::TZ)->toDateString();

        $query = AttendanceRecord::with(['employee.department', 'employee.position']);

        if ($user->can('attendance.manage')) {
            $requestedEmployee = $request->integer('employee_id');
            if ($requestedEmployee) {
                $query->where('employee_id', $requestedEmployee);
            }
        } elseif ($user->hasRole('manager') && $user->employee) {
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $user->employee->id));
        } else {
            $query->where('employee_id', $user->employee?->id ?? 0);
        }

        $period = $request->string('period', 'daily')->toString();
        $this->applyPeriodFilter($query, $request, $period, $todayStr);

        $records = $query
            ->when($request->filled('department_id') && $user->can('attendance.manage'), function ($q) use ($request) {
                $q->whereHas('employee', fn ($sub) => $sub->where('department_id', $request->integer('department_id')));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('work_date')
            ->paginate(20)
            ->withQueryString();

        $todayRecord = null;
        if ($user->employee) {
            $todayRecord = AttendanceRecord::where('employee_id', $user->employee->id)
                ->whereDate('work_date', $todayStr)
                ->first();
        }

        $departments = $user->can('attendance.manage') ? Department::orderBy('name')->get() : collect();
        $employees = $user->can('attendance.manage')
            ? Employee::where('employment_status', 'active')->orderBy('full_name')->get()
            : collect();

        return view('attendance.index', compact('records', 'todayRecord', 'departments', 'employees', 'period'));
    }

    public function create(): View
    {
        $user = request()->user();
        $employee = $user->employee;
        abort_unless($employee && $employee->isActive(), 403);

        return view('attendance.check', ['employee' => $employee, 'record' => $this->openRecord($employee)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;
        abort_unless($employee && $employee->isActive(), 403);

        try {
            $record = $this->service->checkIn($employee, $request->ip());
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        $late = $record->status === 'late' ? " Anda terlambat {$record->late_minutes} menit." : '';

        return redirect()->route('attendance.index')
            ->with('success', "Check-in berhasil pukul {$record->check_in_at->timezone(AttendanceService::TZ)->format('H:i')}.{$late}");
    }

    public function checkout(Request $request): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;
        abort_unless($employee && $employee->isActive(), 403);

        try {
            $record = $this->service->checkOut($employee, $request->ip());
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('attendance.index')
            ->with('success', "Check-out berhasil pukul {$record->check_out_at->timezone(AttendanceService::TZ)->format('H:i')}.");
    }

    public function show(AttendanceRecord $attendance): View
    {
        $this->authorize('view', $attendance);

        $attendance->load(['employee.department', 'employee.position', 'corrections']);

        return view('attendance.show', ['record' => $attendance]);
    }

    private function openRecord(Employee $employee): ?AttendanceRecord
    {
        return AttendanceRecord::where('employee_id', $employee->id)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->orderByDesc('work_date')
            ->first();
    }

    private function applyPeriodFilter($query, Request $request, string $period, string $todayStr): void
    {
        if ($period === 'monthly') {
            $month = $request->string('month', Carbon::today(AttendanceService::TZ)->format('Y-m'))->toString();
            $parts = explode('-', $month);
            $query->whereYear('work_date', (int) ($parts[0] ?? now()->year))
                ->whereMonth('work_date', (int) ($parts[1] ?? now()->month));

            return;
        }

        if ($period === 'range' && $request->filled(['from', 'to'])) {
            $query->whereDate('work_date', '>=', $request->date('from'))
                ->whereDate('work_date', '<=', $request->date('to'));

            return;
        }

        $date = $request->filled('date') ? $request->date('date')->toDateString() : $todayStr;
        $query->whereDate('work_date', $date);
    }
}
