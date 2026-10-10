<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Position::class);

        $positions = Position::with(['department'])
            ->withCount('employees')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            })
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->string('status') === 'active');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();

        return view('positions.index', compact('positions', 'departments'));
    }

    public function create(): View
    {
        $this->authorize('create', Position::class);

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('positions.create', compact('departments'));
    }

    public function store(StorePositionRequest $request): RedirectResponse
    {
        $position = Position::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('positions.index')->with('success', "Jabatan {$position->name} berhasil ditambahkan.");
    }

    public function edit(Position $position): View
    {
        $this->authorize('update', $position);

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('positions.edit', compact('position', 'departments'));
    }

    public function update(UpdatePositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('positions.index')->with('success', "Jabatan {$position->name} berhasil diperbarui.");
    }

    public function destroy(Position $position): RedirectResponse
    {
        $this->authorize('delete', $position);

        if ($position->employees()->exists()) {
            return back()->with('error', "Jabatan {$position->name} tidak dapat dihapus karena masih memiliki karyawan.");
        }

        $position->delete();

        return redirect()->route('positions.index')->with('success', "Jabatan {$position->name} berhasil dihapus.");
    }
}
