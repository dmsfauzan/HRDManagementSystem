<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
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

        return response()->json([
            'data' => collect($records->items())->map(fn (AttendanceRecord $r) => [
                'work_date' => $r->work_date?->format('Y-m-d'),
                'check_in_at' => $r->check_in_at?->timezone(tz())->toDateTimeString(),
                'check_out_at' => $r->check_out_at?->timezone(tz())->toDateTimeString(),
                'status' => $r->status,
                'late_minutes' => $r->late_minutes,
                'work_minutes' => $r->work_minutes,
            ]),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee && $employee->isActive(), 403);

        try {
            $record = $this->service->checkIn($employee, $request->ip());
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'message' => 'Check-in berhasil.',
            'data' => [
                'work_date' => $record->work_date?->format('Y-m-d'),
                'check_in_at' => $record->check_in_at?->timezone(tz())->toDateTimeString(),
                'status' => $record->status,
                'late_minutes' => $record->late_minutes,
            ],
        ]);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee && $employee->isActive(), 403);

        try {
            $record = $this->service->checkOut($employee, $request->ip());
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'message' => 'Check-out berhasil.',
            'data' => [
                'work_date' => $record->work_date?->format('Y-m-d'),
                'check_out_at' => $record->check_out_at?->timezone(tz())->toDateTimeString(),
                'work_minutes' => $record->work_minutes,
            ],
        ]);
    }
}
