<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        return ApiResponse::success([
            ...ApiResponse::userPayload($user),
            'employee' => $employee ? [
                'employee_id' => $employee->employee_id,
                'full_name' => $employee->full_name,
                'department' => $employee->department?->name,
                'position' => $employee->position?->name,
                'employment_status' => $employee->employment_status,
                'join_date' => $employee->join_date?->format('Y-m-d'),
            ] : null,
        ]);
    }
}
