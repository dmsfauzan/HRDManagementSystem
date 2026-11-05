<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceApiController extends Controller
{
    public function __construct(private readonly AttendanceService $service) {}

    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'Akun tidak tertaut ke data karyawan.');

        $records = AttendanceRecord::where('employee_id', $employee->id)
            ->orderByDesc('work_date')
            ->paginate(20);

        return ApiResponse::paginated($records, fn (AttendanceRecord $r) => [
            'work_date' => $r->work_date?->format('Y-m-d'),
            'check_in_at' => $r->check_in_at?->timezone(tz())->toDateTimeString(),
            'check_out_at' => $r->check_out_at?->timezone(tz())->toDateTimeString(),
            'status' => $r->status,
            'late_minutes' => $r->late_minutes,
            'work_minutes' => $r->work_minutes,
        ]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee && $employee->isActive(), 403);

        try {
            $record = $this->service->checkIn($employee, $request->ip());
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }

        return ApiResponse::success([
            'work_date' => $record->work_date?->format('Y-m-d'),
            'check_in_at' => $record->check_in_at?->timezone(tz())->toDateTimeString(),
            'status' => $record->status,
            'late_minutes' => $record->late_minutes,
        ], 'Check-in berhasil.');
    }

    public function checkOut(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee && $employee->isActive(), 403);

        try {
            $record = $this->service->checkOut($employee, $request->ip());
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }

        return ApiResponse::success([
            'work_date' => $record->work_date?->format('Y-m-d'),
            'check_out_at' => $record->check_out_at?->timezone(tz())->toDateTimeString(),
            'work_minutes' => $record->work_minutes,
        ], 'Check-out berhasil.');
    }
}
