<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('employees/{employee}/photo', [EmployeeController::class, 'photo'])
        ->name('employees.photo');

    Route::patch('employees/{employee}/deactivate', [EmployeeController::class, 'deactivate'])
        ->name('employees.deactivate');
    Route::patch('employees/{employee}/activate', [EmployeeController::class, 'activate'])
        ->name('employees.activate');

    Route::resource('employees', EmployeeController::class);

    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('attendance/today', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('attendance/check-in', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::post('attendance/check-out', [AttendanceController::class, 'checkout'])->name('attendance.checkout');
    Route::get('attendance/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');

    Route::get('attendance-corrections', [AttendanceCorrectionController::class, 'index'])->name('attendance.corrections.index');
    Route::get('attendance-corrections/create', [AttendanceCorrectionController::class, 'create'])->name('attendance.corrections.create');
    Route::post('attendance-corrections', [AttendanceCorrectionController::class, 'store'])->name('attendance.corrections.store');
    Route::patch('attendance-corrections/{correction}/decide', [AttendanceCorrectionController::class, 'decide'])->name('attendance.corrections.decide');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::patch('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    Route::get('leave/approvals', [LeaveController::class, 'approvals'])->name('leave.approvals');
    Route::get('leave/create', [LeaveController::class, 'create'])->name('leave.create');
    Route::post('leave', [LeaveController::class, 'store'])->name('leave.store');
    Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::get('leave/{leave}/attachment', [LeaveController::class, 'attachment'])->name('leave.attachment');
    Route::patch('leave/{leave}/decide', [LeaveController::class, 'decide'])->name('leave.decide');
    Route::patch('leave/{leave}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');
    Route::get('leave/{leave}', [LeaveController::class, 'show'])->name('leave.show');

    Route::get('leave-balances', [LeaveBalanceController::class, 'index'])->name('leave.balances.index');
    Route::post('leave-balances', [LeaveBalanceController::class, 'store'])->name('leave.balances.store');
    Route::patch('leave-balances/{balance}/adjust', [LeaveBalanceController::class, 'adjust'])->name('leave.balances.adjust');

    Route::get('leave-types', [LeaveTypeController::class, 'index'])->name('leave.types.index');
    Route::post('leave-types', [LeaveTypeController::class, 'store'])->name('leave.types.store');
    Route::put('leave-types/{type}', [LeaveTypeController::class, 'update'])->name('leave.types.update');

    Route::middleware('permission:reports.view')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/employees', [ReportController::class, 'employees'])->name('reports.employees');
        Route::get('reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('reports/leaves', [ReportController::class, 'leaves'])->name('reports.leaves');
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
        Route::patch('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
        Route::get('roles', function () {
            return view('roles.index', ['roles' => Role::withCount('users')->with('permissions')->get()]);
        })->middleware('permission:roles.manage')->name('roles.index');
    });

    Route::middleware('permission:audit_logs.view')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    });

    Route::middleware('permission:departments.manage')->group(function () {
        Route::resource('departments', DepartmentController::class);
    });

    Route::middleware('permission:positions.manage')->group(function () {
        Route::resource('positions', PositionController::class)->except('show');
    });
});

require __DIR__.'/auth.php';
