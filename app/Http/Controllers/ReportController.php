<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Overtime;
use App\Services\AuditLogger;
use App\Services\CsvExporter;
use App\Services\XlsxWriter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\LazyCollection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

    public function employees(Request $request): View|StreamedResponse|BinaryFileResponse
    {
        $this->authorizeReports();

        $query = $this->employeeQuery($request);
        $headers = CsvExporter::employeeHeaders();
        $rows = fn (): LazyCollection => $query->cursor()->map(fn (Employee $e) => [
            $e->employee_id, $e->full_name, $e->email, $e->department->name ?? '',
            $e->position->name ?? '', $e->manager->full_name ?? '',
            $e->join_date?->format('d/m/Y'), $e->employment_type, $e->employment_status,
        ]);

        if ($response = $this->exportResponse($request, 'employees', 'laporan-karyawan', $headers, $rows, ['search', 'department_id', 'status'])) {
            return $response;
        }

        $employees = $query->paginate(20)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('reports.employees', compact('employees', 'departments'));
    }

    public function attendance(Request $request): View|StreamedResponse|BinaryFileResponse
    {
        $this->authorizeReports();

        $query = AttendanceRecord::with(['employee.department'])
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($s) => $s->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        $this->applyDateFilter($query, $request);
        $query->orderByDesc('work_date');

        $headers = CsvExporter::attendanceHeaders();
        $rows = fn (): LazyCollection => $query->cursor()->map(fn (AttendanceRecord $r) => [
            $r->work_date?->format('d/m/Y'),
            $r->employee->employee_id ?? '',
            $r->employee->full_name ?? '',
            $r->employee->department->name ?? '',
            $r->check_in_at?->timezone(tz())->format('H:i') ?? '',
            $r->check_out_at?->timezone(tz())->format('H:i') ?? '',
            $r->late_minutes,
            $r->work_minutes,
            $r->status,
        ]);

        if ($response = $this->exportResponse($request, 'attendance', 'laporan-absensi', $headers, $rows, ['department_id', 'status', 'period', 'from', 'to'])) {
            return $response;
        }

        $records = $query->paginate(25)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('reports.attendance', compact('records', 'departments'));
    }

    public function recruitment(Request $request): View|StreamedResponse|BinaryFileResponse
    {
        $this->authorizeReports();

        $query = JobApplication::with(['jobPosting.department'])
            ->when($request->filled('posting_id'), fn ($q) => $q->where('job_posting_id', $request->integer('posting_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest();

        $headers = ['Kandidat', 'Posisi', 'Departemen', 'Status', 'Sumber', 'Tgl Lamar', 'Tgl Hired', 'Hari ke-hire'];

        $rows = fn (): LazyCollection => $query->cursor()->map(function (JobApplication $a) {
            $days = $a->hired_at ? $a->created_at->diffInDays($a->hired_at) : '';

            return [
                $a->candidate_name,
                $a->jobPosting->title ?? '',
                $a->jobPosting->department->name ?? '',
                $a->status,
                $a->source ?? '',
                $a->created_at?->format('d/m/Y'),
                $a->hired_at?->format('d/m/Y'),
                $days,
            ];
        });

        if ($response = $this->exportResponse($request, 'recruitment', 'laporan-rekrutmen', $headers, $rows, ['posting_id', 'status', 'from', 'to'])) {
            return $response;
        }

        $applications = $query->paginate(20)->withQueryString();

        $funnelQuery = JobApplication::query()
            ->when($request->filled('posting_id'), fn ($q) => $q->where('job_posting_id', $request->integer('posting_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')));

        $funnel = (clone $funnelQuery)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (clone $funnelQuery)->count();
        $hired = (int) ($funnel['hired'] ?? 0);
        $avgTimeToHire = (clone $funnelQuery)->whereNotNull('hired_at')->get()
            ->avg(fn ($a) => $a->created_at->diffInDays($a->hired_at));
        $sources = (clone $funnelQuery)->selectRaw("coalesce(source, 'lainnya') as source, count(*) as total")->groupBy('source')->pluck('total', 'source');

        return view('reports.recruitment', [
            'applications' => $applications,
            'postings' => JobPosting::orderBy('title')->get(),
            'funnel' => $funnel,
            'total' => $total,
            'hired' => $hired,
            'avgTimeToHire' => $avgTimeToHire,
            'sources' => $sources,
        ]);
    }

    public function overtime(Request $request): View|StreamedResponse|BinaryFileResponse
    {
        $this->authorizeReports();

        $query = Overtime::with(['employee.department', 'approver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($s) => $s->where('department_id', $request->integer('department_id'))));

        $period = $request->string('period', 'monthly')->toString();

        if ($period === 'range' && $request->filled(['from', 'to'])) {
            $query->whereDate('date', '>=', $request->date('from'))->whereDate('date', '<=', $request->date('to'));
        } elseif ($period === 'daily') {
            $query->whereDate('date', $request->filled('date') ? $request->date('date') : tz_today());
        } else {
            $month = $request->string('month', tz_today()->format('Y-m'))->toString();
            $parts = explode('-', $month);
            $query->whereYear('date', (int) ($parts[0] ?? now()->year))->whereMonth('date', (int) ($parts[1] ?? now()->month));
        }

        $query->orderByDesc('date');

        $headers = ['Tanggal', 'Karyawan', 'Departemen', 'Mulai', 'Selesai', 'Durasi (menit)', 'Status', 'Disetujui Oleh'];

        $rows = fn (): LazyCollection => $query->cursor()->map(fn (Overtime $o) => [
            $o->date?->format('d/m/Y'),
            $o->employee->full_name ?? '',
            $o->employee->department->name ?? '',
            $o->startLabel(),
            $o->endLabel(),
            $o->duration_minutes,
            $o->status,
            $o->approver->name ?? '',
        ]);

        if ($response = $this->exportResponse($request, 'overtime', 'laporan-lembur', $headers, $rows, ['status', 'department_id', 'period', 'from', 'to'])) {
            return $response;
        }

        $overtimes = $query->paginate(25)->withQueryString();
        $approvedMinutes = (clone $query)->where('status', 'approved')->sum('duration_minutes');
        $departments = Department::orderBy('name')->get();

        return view('reports.overtime', compact('overtimes', 'approvedMinutes', 'departments', 'period'));
    }

    public function leaves(Request $request): View|StreamedResponse|BinaryFileResponse
    {
        $this->authorizeReports();

        $query = LeaveRequest::with(['employee.department', 'leaveType', 'approver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('leave_type_id'), fn ($q) => $q->where('leave_type_id', $request->integer('leave_type_id')))
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($s) => $s->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('start_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('start_date', '<=', $request->date('to')))
            ->latest();

        $headers = CsvExporter::leaveHeaders();
        $rows = fn (): LazyCollection => $query->cursor()->map(fn (LeaveRequest $l) => [
            $l->employee->full_name ?? '',
            $l->leaveType->name ?? '',
            $l->start_date?->format('d/m/Y'),
            $l->end_date?->format('d/m/Y'),
            $l->total_days,
            $l->reason,
            $l->status,
            $l->approver->name ?? '',
            $l->decided_at?->format('d/m/Y H:i') ?? '',
        ]);

        if ($response = $this->exportResponse($request, 'leaves', 'laporan-cuti', $headers, $rows, ['status', 'leave_type_id', 'department_id', 'from', 'to'])) {
            return $response;
        }

        $requests = $query->paginate(20)->withQueryString();
        $leaveTypes = LeaveType::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('reports.leaves', compact('requests', 'leaveTypes', 'departments'));
    }

    /**
     * @param  callable():LazyCollection  $rows
     */
    private function exportResponse(Request $request, string $report, string $basename, array $headers, callable $rows, array $filterKeys): View|StreamedResponse|BinaryFileResponse|null
    {
        $format = $request->input('format', $request->input('export'));

        if (! in_array($format, ['csv', 'xlsx', 'print'], true)) {
            return null;
        }

        AuditLogger::log('report.exported', null, [], [
            'report' => $report,
            'format' => $format,
            'filters' => $request->only($filterKeys),
        ]);

        $stamp = now()->format('Ymd-His');

        return match ($format) {
            'csv' => CsvExporter::stream("{$basename}-{$stamp}.csv", $headers, $rows()),
            'xlsx' => XlsxWriter::download("{$basename}-{$stamp}.xlsx", $headers, $rows()),
            'print' => view('reports.print', [
                'title' => str($basename)->replace('-', ' ')->title()->toString(),
                'headers' => $headers,
                'rows' => $rows(),
            ]),
        };
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
            $query->whereDate('work_date', $request->filled('date') ? $request->date('date') : Carbon::today(tz()));

            return;
        }

        $month = $request->string('month', Carbon::today(tz())->format('Y-m'))->toString();
        $parts = explode('-', $month);
        $query->whereYear('work_date', (int) ($parts[0] ?? now()->year))
            ->whereMonth('work_date', (int) ($parts[1] ?? now()->month));
    }

    private function authorizeReports(): void
    {
        abort_unless(auth()->user()->can('reports.view'), 403);
    }
}
