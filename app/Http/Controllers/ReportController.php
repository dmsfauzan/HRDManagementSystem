<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\AuditLogger;
use App\Services\CsvExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        $this->authorizeReports();

        $stats = [
            'employees' => Employee::where('employment_status', 'active')->count(),
            'departments' => Department::count(),
            'attendance_this_month' => AttendanceRecord::whereYear('work_date', now()->year)->whereMonth('work_date', now()->month)->count(),
            'leaves_this_year' => LeaveRequest::whereYear('created_at', now()->year)->count(),
        ];

        return view('reports.index', compact('stats'));
    }

    public function employees(Request $request): View|StreamedResponse
    {
        $this->authorizeReports();

        $query = $this->employeeQuery($request);

        if ($request->input('export') === 'csv') {
            AuditLogger::log('report.exported', null, [], ['report' => 'employees', 'filters' => $request->only(['search', 'department_id', 'status'])]);

            return CsvExporter::stream(
                'laporan-karyawan-'.now()->format('Ymd-His').'.csv',
                CsvExporter::employeeHeaders(),
                $query->cursor()->map(fn (Employee $e) => [
                    $e->employee_id, $e->full_name, $e->email, $e->department->name ?? '',
                    $e->position->name ?? '', $e->manager->full_name ?? '',
                    $e->join_date?->format('d/m/Y'), $e->employment_type, $e->employment_status,
                ])
            );
        }

        $employees = $query->paginate(20)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('reports.employees', compact('employees', 'departments'));
    }

    public function attendance(Request $request): View|StreamedResponse
    {
        $this->authorizeReports();

        $query = AttendanceRecord::with(['employee.department'])
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($s) => $s->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        $this->applyDateFilter($query, $request);
        $query->orderByDesc('work_date');

        if ($request->input('export') === 'csv') {
            AuditLogger::log('report.exported', null, [], ['report' => 'attendance', 'filters' => $request->only(['department_id', 'status', 'period', 'from', 'to'])]);

            return CsvExporter::stream(
                'laporan-absensi-'.now()->format('Ymd-His').'.csv',
                CsvExporter::attendanceHeaders(),
                $query->cursor()->map(fn (AttendanceRecord $r) => [
                    $r->work_date?->format('d/m/Y'),
                    $r->employee->employee_id ?? '',
                    $r->employee->full_name ?? '',
                    $r->employee->department->name ?? '',
                    $r->check_in_at?->timezone('Asia/Jakarta')->format('H:i') ?? '',
                    $r->check_out_at?->timezone('Asia/Jakarta')->format('H:i') ?? '',
                    $r->late_minutes,
                    $r->work_minutes,
                    $r->status,
                ])
            );
        }

        $records = $query->paginate(25)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('reports.attendance', compact('records', 'departments'));
    }

    public function leaves(Request $request): View|StreamedResponse
    {
        $this->authorizeReports();

        $query = LeaveRequest::with(['employee.department', 'leaveType', 'approver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('leave_type_id'), fn ($q) => $q->where('leave_type_id', $request->integer('leave_type_id')))
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($s) => $s->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('start_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('start_date', '<=', $request->date('to')))
            ->latest();

        if ($request->input('export') === 'csv') {
            AuditLogger::log('report.exported', null, [], ['report' => 'leaves', 'filters' => $request->only(['status', 'leave_type_id', 'department_id', 'from', 'to'])]);

            return CsvExporter::stream(
                'laporan-cuti-'.now()->format('Ymd-His').'.csv',
                CsvExporter::leaveHeaders(),
                $query->cursor()->map(fn (LeaveRequest $l) => [
                    $l->employee->full_name ?? '',
                    $l->leaveType->name ?? '',
                    $l->start_date?->format('d/m/Y'),
                    $l->end_date?->format('d/m/Y'),
                    $l->total_days,
                    $l->reason,
                    $l->status,
                    $l->approver->name ?? '',
                    $l->decided_at?->format('d/m/Y H:i') ?? '',
                ])
            );
        }

        $requests = $query->paginate(20)->withQueryString();
        $leaveTypes = LeaveType::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('reports.leaves', compact('requests', 'leaveTypes', 'departments'));
    }

    private function employeeQuery(Request $request)
    {
        return Employee::with(['department', 'position', 'manager'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($sub) => $sub->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%"));
            })
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('employment_status', $request->string('status')))
            ->latest();
    }

    private function applyDateFilter($query, Request $request): void
    {
        $period = $request->string('period', 'monthly')->toString();

        if ($period === 'range' && $request->filled(['from', 'to'])) {
            $query->whereDate('work_date', '>=', $request->date('from'))
                ->whereDate('work_date', '<=', $request->date('to'));

            return;
        }

        if ($period === 'daily') {
            $query->whereDate('work_date', $request->filled('date') ? $request->date('date') : Carbon::today('Asia/Jakarta'));

            return;
        }

        $month = $request->string('month', Carbon::today('Asia/Jakarta')->format('Y-m'))->toString();
        $parts = explode('-', $month);
        $query->whereYear('work_date', (int) ($parts[0] ?? now()->year))
            ->whereMonth('work_date', (int) ($parts[1] ?? now()->month));
    }

    private function authorizeReports(): void
    {
        abort_unless(auth()->user()->can('reports.view'), 403);
    }
}
