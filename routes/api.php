<?php

use App\Http\Controllers\Api\V1\AnnouncementApiController;
use App\Http\Controllers\Api\V1\AttendanceApiController;
use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\LeaveApiController;
use App\Http\Controllers\Api\V1\ProfileApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthApiController::class, 'login'])
        ->middleware('throttle:login')
        ->name('api.login');

    Route::middleware(['auth.api', 'throttle:api'])->group(function () {
        Route::get('me', [ProfileApiController::class, 'show']);

        Route::get('attendance', [AttendanceApiController::class, 'index']);
        Route::post('attendance/check-in', [AttendanceApiController::class, 'checkIn']);
        Route::post('attendance/check-out', [AttendanceApiController::class, 'checkOut']);

        Route::get('leave-balances', [LeaveApiController::class, 'balances']);
        Route::get('leaves', [LeaveApiController::class, 'index']);
        Route::post('leaves', [LeaveApiController::class, 'store']);

        Route::get('announcements', [AnnouncementApiController::class, 'index']);
    });
});
