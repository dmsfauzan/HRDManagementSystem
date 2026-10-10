<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustLeaveBalanceRequest;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly LeaveService $service) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        // Karyawan melihat saldonya sendiri; HRD melihat semuanya
        abort_unless($user->can('leave.balances_manage') || $user->employee, 403);

        $year = (int) $request->input('year', now('Asia/Jakarta')->format('Y'));

        $query = LeaveBalance::with(['employee.department', 'leaveType']);

        if ($user->can('leave.balances_manage')) {
            // semua
        } else {
            $query->where('employee_id', $user->employee->id);
        }

        $balances = $query
            ->where('year', $year)
            ->when($request->filled('leave_type_id'), fn ($q) => $q->where('leave_type_id', $request->integer('leave_type_id')))
            ->when(
                $request->filled('search') && $user->can('leave.balances_manage'),
                fn ($q) => $q->whereHas(
                    'employee',
                    fn ($sub) => $sub->where('full_name', 'like', '%'.$request->string('search').'%')
                        ->orWhere('employee_id', 'like', '%'.$request->string('search').'%')
                )
            )
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $leaveTypes = LeaveType::orderBy('name')->get();
        $employees = $user->can('leave.balances_manage')
            ? Employee::where('employment_status', 'active')->orderBy('full_name')->get()
            : collect();

        return view('leave.balances', compact('balances', 'leaveTypes', 'employees', 'year'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('leave.balances_manage'), 403);

        $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'balance' => ['required', 'numeric', 'min:0', 'max:365'],
        ], [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'leave_type_id.required' => 'Jenis cuti wajib dipilih.',
            'year.required' => 'Tahun wajib diisi.',
            'balance.required' => 'Saldo awal wajib diisi.',
        ]);

        $type = LeaveType::findOrFail($request->integer('leave_type_id'));
        $employee = Employee::findOrFail($request->integer('employee_id'));

        $balance = LeaveBalance::firstOrCreate(
            ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $request->integer('year')],
            ['balance' => 0, 'used' => 0]
        );

        $this->service->adjustBalance($balance, (float) $request->input('balance'), $request->user(), 'Saldo awal/manual oleh '.$request->user()->name);

        return back()->with('success', 'Saldo cuti berhasil disimpan.');
    }

    public function adjust(AdjustLeaveBalanceRequest $request, LeaveBalance $balance): RedirectResponse
    {
        $this->service->adjustBalance($balance, (float) $request->input('balance'), $request->user(), $request->input('description'));

        return back()->with('success', 'Saldo cuti berhasil disesuaikan.');
    }
}
