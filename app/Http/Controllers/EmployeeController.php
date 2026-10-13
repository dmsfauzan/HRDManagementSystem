<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\WorkSchedule;
use App\Services\AuditLogger;
use App\Services\EmployeeImportService;
use App\Services\EmployeeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly EmployeeService $service,
        private readonly EmployeeImportService $importService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->cannot('viewAny', Employee::class)) {
            $own = $user->employee;
            if ($own) {
                return redirect()->route('employees.show', $own);
            }
            abort(403);
        }

        $query = Employee::with(['department', 'position', 'manager']);

        if ($user->cannot('employees.manage')) {
            $own = $user->employee;
            $query->where('manager_id', $own?->id);
        }

        $employees = $query
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($sub) use ($search) {
                    $sub->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('employment_status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('employment_type', $request->string('type')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();

        return view('employees.index', compact('employees', 'departments'));
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('employees.create', $this->formData());
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = $this->service->create(
            $request->validated(),
            $request->file('photo')
        );

        return redirect()->route('employees.show', $employee)
            ->with('success', "Karyawan {$employee->full_name} berhasil ditambahkan.");
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load(['department', 'position', 'manager', 'workSchedule', 'subordinates']);

        return view('employees.show', [
            'employee' => $employee,
            'subordinates' => $employee->subordinates,
        ]);
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        return view('employees.edit', array_merge(
            $this->formData(),
            ['employee' => $employee]
        ));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->service->update($employee, $request->validated(), $request->file('photo'));

        return redirect()->route('employees.show', $employee)
            ->with('success', "Data karyawan {$employee->full_name} berhasil diperbarui.");
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $this->service->deletePhoto($employee);
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', "Karyawan {$employee->full_name} berhasil dihapus.");
    }

    public function deactivate(Employee $employee): RedirectResponse
    {
        $this->authorize('deactivate', $employee);

        $this->service->deactivate($employee);

        return redirect()->route('employees.show', $employee)
            ->with('success', "Karyawan {$employee->full_name} berhasil dinonaktifkan.");
    }

    public function offboard(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('deactivate', $employee);

        $reason = $request->string('reason')->toString() ?: null;
        $this->service->offboard($employee, $reason);

        return redirect()->route('employees.show', $employee)
            ->with('success', "Karyawan {$employee->full_name} telah di-offboard (resign).");
    }

    public function bulk(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);

        $data = $request->validate([
            'action' => ['required', 'in:activate,deactivate'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:employees,id'],
        ], [
            'ids.required' => 'Pilih minimal satu karyawan.',
            'action.required' => 'Aksi wajib dipilih.',
        ]);

        $employees = Employee::whereIn('id', $data['ids'])->get();
        $count = 0;

        foreach ($employees as $employee) {
            if (! $request->user()->can('deactivate', $employee)) {
                continue;
            }
            if ($data['action'] === 'deactivate') {
                $this->service->deactivate($employee);
            } else {
                $this->service->activate($employee);
            }
            $count++;
        }

        return back()->with('success', "{$count} karyawan berhasil diproses.");
    }

    public function trashed(): View
    {
        abort_unless(auth()->user()->can('employees.manage'), 403);

        $employees = Employee::onlyTrashed()->with(['department', 'position'])->latest('deleted_at')->paginate(20);

        return view('employees.trashed', compact('employees'));
    }

    public function restore(int $employee): RedirectResponse
    {
        abort_unless(auth()->user()->can('employees.manage'), 403);

        $record = Employee::onlyTrashed()->findOrFail($employee);
        $record->restore();

        AuditLogger::log('employee.restored', $record, [], ['employee_id' => $record->employee_id]);

        return redirect()->route('employees.trashed')->with('success', "Karyawan {$record->full_name} berhasil dipulihkan.");
    }

    public function orgChart(): View
    {
        abort_unless(auth()->user()->can('employees.view'), 403);

        $employees = Employee::with(['position', 'department'])
            ->whereIn('employment_status', ['active', 'inactive'])
            ->orderBy('full_name')
            ->get();

        $byManager = $employees->groupBy('manager_id');
        $roots = $employees->whereNull('manager_id');

        return view('employees.org-chart', compact('byManager', 'roots'));
    }

    public function activate(Employee $employee): RedirectResponse
    {
        $this->authorize('deactivate', $employee);

        $this->service->activate($employee);

        return redirect()->route('employees.show', $employee)
            ->with('success', "Karyawan {$employee->full_name} berhasil diaktifkan.");
    }

    public function photo(Employee $employee): StreamedResponse
    {
        $this->authorize('view', $employee);

        abort_unless($employee->photo_path && Storage::disk('private')->exists($employee->photo_path), 404);

        return Storage::disk('private')->response($employee->photo_path);
    }

    private function formData(): array
    {
        return [
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'positions' => Position::where('is_active', true)->orderBy('name')->get(),
            'managers' => Employee::where('employment_status', 'active')->orderBy('full_name')->get(),
            'schedules' => WorkSchedule::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    public function importCreate(): View
    {
        abort_unless(request()->user()->can('employees.import'), 403);

        return view('employees.import');
    }

    public function import(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('employees.import'), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'file.required' => 'Berkas CSV wajib diunggah.',
            'file.mimes' => 'Berkas harus berformat CSV.',
        ]);

        $result = $this->importService->import($request->file('file'));

        return redirect()->route('employees.import.create')->with('import_result', $result);
    }

    public function importTemplate(): StreamedResponse
    {
        abort_unless(request()->user()->can('employees.import'), 403);

        $headers = ['employee_id', 'full_name', 'email', 'phone', 'department_code', 'position_code', 'manager_employee_id', 'join_date', 'employment_type', 'employment_status'];
        $sample = ['EMP-100', 'Nama Karyawan', 'email@contoh.co.id', '081234567890', 'IT', 'IT-DEV', 'IT-001', date('Y-m-d'), 'permanent', 'active'];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'template-impor-karyawan.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
