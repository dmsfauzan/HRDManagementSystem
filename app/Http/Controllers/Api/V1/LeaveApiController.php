<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaveApiController extends Controller
{
    public function __construct(private readonly LeaveService $service) {}

    public function balances(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'Akun tidak tertaut ke data karyawan.');

        $balances = $employee->leaveBalances()->with('leaveType')->where('year', (int) tz_today()->format('Y'))->get();

        return ApiResponse::success($balances->map(fn ($b) => [
            'leave_type' => $b->leaveType?->name,
            'balance' => (float) $b->balance,
            'used' => (float) $b->used,
            'year' => $b->year,
        ])->values());
    }

    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'Akun tidak tertaut ke data karyawan.');

        $requests = LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->id)
            ->latest()
            ->paginate(20);

        return ApiResponse::paginated($requests, fn (LeaveRequest $l) => [
            'id' => $l->id,
            'leave_type' => $l->leaveType?->name,
            'start_date' => $l->start_date?->format('Y-m-d'),
            'end_date' => $l->end_date?->format('Y-m-d'),
            'total_days' => (float) $l->total_days,
            'status' => $l->status,
            'reason' => $l->reason,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'Akun tidak tertaut ke data karyawan.');

        $data = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->service->ensureBalances($employee);

        try {
            $leave = $this->service->submit($employee, $data);
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }

        return ApiResponse::success([
            'id' => $leave->id,
            'status' => $leave->status,
            'total_days' => (float) $leave->total_days,
        ], 'Pengajuan cuti berhasil dikirim.', 201);
    }
}
