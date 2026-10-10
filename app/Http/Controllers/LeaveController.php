<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecideLeaveRequest;
use App\Http\Requests\StoreLeaveRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveDecided;
use App\Notifications\LeaveSubmitted;
use App\Services\LeaveService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly LeaveService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $user = $request->user();

        $query = LeaveRequest::with(['employee.department', 'leaveType', 'approver']);

        if ($user->can('leave.manage')) {
            // semua
        } elseif ($user->hasRole('manager') && $user->employee) {
            $scope = $request->string('scope', 'team')->toString();
            if ($scope === 'team') {
                $query->whereHas('employee', fn ($q) => $q->where('manager_id', $user->employee->id));
            } else {
                $query->where('employee_id', $user->employee->id);
            }
        } else {
            $query->where('employee_id', $user->employee?->id ?? 0);
        }

        $requests = $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('leave_type_id'), fn ($q) => $q->where('leave_type_id', $request->integer('leave_type_id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $leaveTypes = LeaveType::orderBy('name')->get();

        return view('leave.index', compact('requests', 'leaveTypes'));
    }

    public function approvals(Request $request): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $user = $request->user();
        abort_unless($user->can('leave.manage') || $user->can('leave.approve'), 403);

        $query = LeaveRequest::with(['employee.department', 'leaveType'])
            ->where('status', 'pending');

        if ($user->can('leave.manage')) {
            // semua pending
        } elseif ($user->hasRole('manager') && $user->employee) {
            $query->whereHas('employee', fn ($q) => $q->where('manager_id', $user->employee->id));
        } else {
            $query->whereRaw('1=0');
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        return view('leave.approvals', compact('requests'));
    }

    public function decide(DecideLeaveRequest $request, LeaveRequest $leave): RedirectResponse
    {
        $action = $request->string('action')->toString();

        try {
            $leave = $this->service->decide($leave, $request->user(), $action, $request->input('decision_note'));
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($leave->employee?->user) {
            $leave->employee->user->notify(new LeaveDecided($leave));
        }

        return back()->with('success', $action === 'approved' ? 'Pengajuan cuti disetujui.' : 'Pengajuan cuti ditolak.');
    }

    public function create(): View
    {
        $this->authorize('create', LeaveRequest::class);

        $types = LeaveType::where('is_active', true)->orderBy('name')->get();
        $y = (int) now('Asia/Jakarta')->format('Y');
        $me = auth()->user()->employee;
        $balances = $me
            ? $me->leaveBalances()->with('leaveType')->where('year', $y)->get()
            : collect();

        return view('leave.create', compact('types', 'balances'));
    }

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403);

        $this->service->ensureBalances($employee);

        try {
            $leave = $this->service->submit($employee, $request->validated(), $request->file('attachment'));
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        $this->notifyApprovers($leave);

        return redirect()->route('leave.show', $leave)
            ->with('success', 'Pengajuan cuti berhasil dikirim dan menunggu persetujuan.');
    }

    public function show(LeaveRequest $leave): View
    {
        $this->authorize('view', $leave);

        $leave->load(['employee.department', 'employee.position', 'leaveType', 'approver', 'approvalActions.actor']);

        return view('leave.show', compact('leave'));
    }

    public function cancel(LeaveRequest $leave): RedirectResponse
    {
        $this->authorize('cancel', $leave);

        try {
            $leave = $this->service->cancel($leave, auth()->user());
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        $recipient = $leave->employee?->user;
        if ($recipient && $recipient->id !== auth()->id()) {
            $recipient->notify(new LeaveDecided($leave));
        }

        // Notifikasi ke HRD juga
        $leave->fresh();
        $this->notifyApprovers($leave->fresh());

        return back()->with('success', 'Pengajuan cuti dibatalkan.');
    }

    public function attachment(LeaveRequest $leave): StreamedResponse
    {
        $this->authorize('view', $leave);

        abort_unless($leave->attachment_path && Storage::disk('private')->exists($leave->attachment_path), 404);

        return Storage::disk('private')->response($leave->attachment_path);
    }

    private function notifyApprovers(LeaveRequest $leave): void
    {
        $recipients = User::permission('leave.approve')->get();

        $manager = $leave->employee?->manager?->user;
        if ($manager && ! $recipients->contains('id', $manager->id)) {
            $recipients->push($manager);
        }

        $recipients = $recipients->reject(fn (User $u) => $u->id === auth()->id());

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new LeaveSubmitted($leave));
        }
    }
}
