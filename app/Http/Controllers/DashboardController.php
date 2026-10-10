<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['super_admin', 'hrd_admin'])) {
            return $this->hrdDashboard();
        }

        if ($user->hasRole('manager')) {
            return $this->managerDashboard();
        }

        return $this->employeeDashboard();
    }

    private function hrdDashboard()
    {
        $today = Carbon::today('Asia/Jakarta');
        $todayStr = $today->toDateString();
        $service = app(AttendanceService::class);

        $totalActiveEmployees = Employee::where('employment_status', 'active')->count();

        $isHoliday = Holiday::whereDate('date', $todayStr)->exists();

        $expectedIds = $isHoliday
            ? collect()
            : Employee::with('workSchedule')
                ->where('employment_status', 'active')
                ->get()
                ->filter(fn (Employee $e) => $service->isScheduledWorkDay($e, $today))
                ->pluck('id');

        $presentToday = AttendanceRecord::whereDate('work_date', $todayStr)
            ->whereIn('status', AttendanceStatus::presentLike())->count();
        $lateToday = AttendanceRecord::whereDate('work_date', $todayStr)
            ->where('status', AttendanceStatus::Late->value)->count();

        $presentExpected = AttendanceRecord::whereDate('work_date', $todayStr)
            ->whereIn('employee_id', $expectedIds)
            ->whereIn('status', AttendanceStatus::presentLike())->count();
        $leaveExpected = AttendanceRecord::whereDate('work_date', $todayStr)
            ->whereIn('employee_id', $expectedIds)
            ->whereIn('status', [AttendanceStatus::Leave->value, AttendanceStatus::Permit->value])->count();
        $absentToday = max(0, $expectedIds->count() - $presentExpected - $leaveExpected);

        $pendingLeaves = LeaveRequest::where('status', 'pending')->count();
        $employeesPerDepartment = Department::withCount(['employees' => fn ($q) => $q->where('employment_status', 'active')])->get();
        $recentEmployees = Employee::with(['department', 'position'])->latest()->take(5)->get();
        $recentLeaves = LeaveRequest::with(['employee', 'leaveType'])->latest()->take(5)->get();
        $recentAttendance = AttendanceRecord::with('employee')->latest('work_date')->take(6)->get();

        return view('dashboard.hrd', compact(
            'totalActiveEmployees', 'presentToday', 'lateToday', 'absentToday', 'pendingLeaves',
            'employeesPerDepartment', 'recentEmployees', 'recentLeaves', 'recentAttendance',
            'today', 'isHoliday'
        ));
    }

    private function managerDashboard()
    {
        $user = Auth::user();
        $today = Carbon::today('Asia/Jakarta');

        $myEmployee = Employee::where('user_id', $user->id)->first();
        $teamQuery = Employee::query()->when($myEmployee, fn ($q) => $q->where('manager_id', $myEmployee->id));

        $teamCount = (clone $teamQuery)->where('employment_status', 'active')->count();
        $teamIds = (clone $teamQuery)->pluck('id');
        $teamPresentToday = AttendanceRecord::whereIn('employee_id', $teamIds)->whereDate('work_date', $today)->whereIn('status', AttendanceStatus::presentLike())->count();
        $teamPendingLeaves = LeaveRequest::whereIn('employee_id', $teamIds)->where('status', 'pending')->count();
        $members = (clone $teamQuery)->with(['department', 'position'])->where('employment_status', 'active')->get();
        $pendingLeaves = LeaveRequest::with(['employee', 'leaveType'])->whereIn('employee_id', $teamIds)->where('status', 'pending')->latest()->take(8)->get();

        return view('dashboard.manager', compact(
            'teamCount', 'teamPresentToday', 'teamPendingLeaves', 'members', 'pendingLeaves', 'today'
        ));
    }

    private function employeeDashboard()
    {
        $user = Auth::user();
        $today = Carbon::today('Asia/Jakarta');

        $employee = Employee::with(['department', 'position', 'workSchedule'])->where('user_id', $user->id)->first();
        $attendanceToday = $employee
            ? AttendanceRecord::where('employee_id', $employee->id)->whereDate('work_date', $today)->first()
            : null;
        $recentAttendance = $employee
            ? AttendanceRecord::where('employee_id', $employee->id)->latest('work_date')->take(7)->get()
            : collect();
        $leaveBalances = $employee
            ? $employee->leaveBalances()->with('leaveType')->where('year', (int) $today->format('Y'))->get()
            : collect();
        $recentLeaves = $employee
            ? LeaveRequest::with('leaveType')->where('employee_id', $employee->id)->latest()->take(5)->get()
            : collect();
        $notifications = $user->notifications()->latest()->take(6)->get();
        $schedule = $employee?->workSchedule;

        return view('dashboard.employee', compact(
            'employee', 'attendanceToday', 'recentAttendance', 'leaveBalances',
            'recentLeaves', 'notifications', 'schedule', 'today'
        ));
    }
}
