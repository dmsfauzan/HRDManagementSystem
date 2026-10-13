<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ShiftSwap;
use App\Services\ShiftSwapService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShiftSwapController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ShiftSwapService $service) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->employee || $user->can('attendance.manage') || $user->hasRole('manager'), 403);

        $query = ShiftSwap::with(['requester', 'target', 'approver']);

        if ($user->can('attendance.manage')) {
            // semua
        } elseif ($user->hasRole('manager') && $user->employee) {
            $query->whereHas('requester', fn ($q) => $q->where('manager_id', $user->employee->id));
        } else {
            $query->where(fn ($q) => $q->where('requester_employee_id', $user->employee->id)
                ->orWhere('target_employee_id', $user->employee->id));
        }

        $swaps = $query->latest()->paginate(20)->withQueryString();

        return view('shift-swaps.index', compact('swaps'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->employee, 403);

        $me = $request->user()->employee;
        $employees = Employee::where('employment_status', 'active')
            ->whereKeyNot($me->id)
            ->orderBy('full_name')
            ->get();

        return view('shift-swaps.create', compact('employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403);

        $data = $request->validate([
            'target_employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'target_employee_id.required' => 'Rekan tukar shift wajib dipilih.',
            'date.required' => 'Tanggal wajib diisi.',
            'date.after_or_equal' => 'Tanggal tidak boleh di masa lalu.',
            'reason.required' => 'Alasan wajib diisi.',
        ]);

        try {
            $this->service->submit($employee, $data);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('shift-swaps.index')->with('success', 'Pengajuan tukar shift berhasil dikirim.');
    }

    public function decide(Request $request, ShiftSwap $shiftSwap): RedirectResponse
    {
        $this->authorizeDecide($request, $shiftSwap);

        $data = $request->validate([
            'action' => ['required', 'in:approved,rejected'],
            'decision_note' => ['required_if:action,rejected', 'nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->decide($shiftSwap, $request->user(), $data['action'], $data['decision_note'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Keputusan tukar shift disimpan.');
    }

    public function cancel(Request $request, ShiftSwap $shiftSwap): RedirectResponse
    {
        $user = $request->user();
        $own = $user->employee && $user->employee->id === $shiftSwap->requester_employee_id;
        abort_unless($own || $user->can('attendance.manage'), 403);

        try {
            $this->service->cancel($shiftSwap, $user);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan tukar shift dibatalkan.');
    }

    private function authorizeDecide(Request $request, ShiftSwap $swap): void
    {
        $user = $request->user();

        if ($user->can('attendance.manage')) {
            return;
        }

        $isManager = $user->hasRole('manager') && $user->employee
            && $swap->requester?->manager_id === $user->employee->id;

        abort_unless($isManager, 403);
    }
}
