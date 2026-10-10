<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\WorkSchedule;
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

    public function __construct(private readonly EmployeeService $service) {}

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
}
