<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecideOvertimeRequest;
use App\Http\Requests\StoreOvertimeRequest;
use App\Models\Overtime;
use App\Models\User;
use App\Notifications\OvertimeDecided;
use App\Notifications\OvertimeSubmitted;
use App\Services\OvertimeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OvertimeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly OvertimeService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Overtime::class);

        $user = $request->user();

        $query = Overtime::with(['employee.department', 'approver']);

        if ($user->can('attendance.manage')) {
            // semua
        } elseif ($user->hasRole('manager') && $user->employee) {
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $user->employee->id));
        } else {
            $query->where('employee_id', $user->employee?->id ?? 0);
        }

        $overtimes = $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('date')
            ->paginate(20)
            ->withQueryString();

        return view('overtime.index', compact('overtimes'));
    }

    public function create(): View
    {
        $this->authorize('create', Overtime::class);

        return view('overtime.create');
    }

    public function store(StoreOvertimeRequest $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403);

        try {
            $overtime = $this->service->submit($employee, $request->validated());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        $this->notifyApprovers($overtime);

        return redirect()->route('overtime.index')
            ->with('success', 'Pengajuan lembur berhasil dikirim dan menunggu persetujuan.');
    }

    public function decide(DecideOvertimeRequest $request, Overtime $overtime): RedirectResponse
    {
        try {
            $overtime = $this->service->decide($overtime, $request->user(), $request->string('action')->toString(), $request->input('decision_note'));
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($overtime->employee?->user) {
            $overtime->employee->user->notify(new OvertimeDecided($overtime));
        }

        return back()->with('success', 'Keputusan lembur berhasil disimpan.');
    }

    public function cancel(Overtime $overtime): RedirectResponse
    {
        $this->authorize('cancel', $overtime);

        try {
            $this->service->cancel($overtime, auth()->user());
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan lembur dibatalkan.');
    }

    private function notifyApprovers(Overtime $overtime): void
    {
        $recipients = User::permission('leave.approve')->get();

        $manager = $overtime->employee?->manager?->user;
        if ($manager && ! $recipients->contains('id', $manager->id)) {
            $recipients->push($manager);
        }

        $recipients = $recipients->reject(fn (User $u) => $u->id === auth()->id());

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new OvertimeSubmitted($overtime));
        }
    }
}
