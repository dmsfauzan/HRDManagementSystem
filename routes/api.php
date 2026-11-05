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

    Route::middleware('throttle:api')->group(function () {
        Route::get('me', [ProfileApiController::class, 'show'])->middleware('auth.api');
        Route::post('logout', [AuthApiController::class, 'logout'])->middleware('auth.api')->name('api.logout');

        Route::get('attendance', [AttendanceApiController::class, 'index'])->middleware('auth.api:attendance:read');
        Route::post('attendance/check-in', [AttendanceApiController::class, 'checkIn'])->middleware('auth.api:attendance:write');
        Route::post('attendance/check-out', [AttendanceApiController::class, 'checkOut'])->middleware('auth.api:attendance:write');

        Route::get('leave-balances', [LeaveApiController::class, 'balances'])->middleware('auth.api:leave:read');
        Route::get('leaves', [LeaveApiController::class, 'index'])->middleware('auth.api:leave:read');
        Route::post('leaves', [LeaveApiController::class, 'store'])->middleware('auth.api:leave:write');

        Route::get('announcements', [AnnouncementApiController::class, 'index'])->middleware('auth.api:announcement:read');
    });
});
