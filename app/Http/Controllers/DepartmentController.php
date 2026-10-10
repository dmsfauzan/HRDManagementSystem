<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::withCount('employees')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->string('status') === 'active');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);

        return view('departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('departments.index')->with('success', "Departemen {$department->name} berhasil ditambahkan.");
    }

    public function show(Department $department): View
    {
        $this->authorize('view', $department);

        $department->load(['positions' => fn ($q) => $q->orderBy('name')]);

        return view('departments.show', compact('department'));
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        return view('departments.edit', compact('department'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('departments.index')->with('success', "Departemen {$department->name} berhasil diperbarui.");
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        if ($department->employees()->exists()) {
            return back()->with('error', "Departemen {$department->name} tidak dapat dihapus karena masih memiliki karyawan.");
        }

        $department->delete();

        return redirect()->route('departments.index')->with('success', "Departemen {$department->name} berhasil dihapus.");
    }
}
