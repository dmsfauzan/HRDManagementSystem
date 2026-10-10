<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
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

        $totalActiveEmployees = Employee::where('employment_status', 'active')->count();
        $presentToday = AttendanceRecord::whereDate('work_date', $today)->whereIn('status', ['present', 'late'])->count();
        $lateToday = AttendanceRecord::whereDate('work_date', $today)->where('status', 'late')->count();
        $pendingLeaves = LeaveRequest::where('status', 'pending')->count();
        $employeesPerDepartment = Department::withCount(['employees' => fn ($q) => $q->where('employment_status', 'active')])->get();
        $recentEmployees = Employee::with(['department', 'position'])->latest()->take(5)->get();
        $recentLeaves = LeaveRequest::with(['employee', 'leaveType'])->latest()->take(5)->get();
        $recentAttendance = AttendanceRecord::with('employee')->latest('work_date')->take(6)->get();

        return view('dashboard.hrd', compact(
            'totalActiveEmployees', 'presentToday', 'lateToday', 'pendingLeaves',
            'employeesPerDepartment', 'recentEmployees', 'recentLeaves', 'recentAttendance',
            'today'
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
        $teamPresentToday = AttendanceRecord::whereIn('employee_id', $teamIds)->whereDate('work_date', $today)->whereIn('status', ['present', 'late'])->count();
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
