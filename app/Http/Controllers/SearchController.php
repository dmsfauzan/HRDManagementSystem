<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\JobPosting;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $user = $request->user();
        $like = '%'.$query.'%';
        $results = [];

        // Karyawan (sesuai scope akses)
        if ($user->can('employees.manage') || $user->hasRole('manager') || $user->employee) {
            $employees = Employee::query()
                ->when(! $user->can('employees.manage'), function ($q) use ($user) {
                    if ($user->hasRole('manager') && $user->employee) {
                        $q->where('manager_id', $user->employee->id);
                    } elseif ($user->employee) {
                        $q->whereKey($user->employee->id);
                    }
                })
                ->where(fn ($q) => $q->where('full_name', 'like', $like)->orWhere('employee_id', 'like', $like))
                ->limit(5)
                ->get();

            foreach ($employees as $employee) {
                $results[] = [
                    'type' => 'Karyawan',
                    'label' => $employee->full_name,
                    'sublabel' => $employee->employee_id,
                    'url' => route('employees.show', $employee),
                ];
            }
        }

        // Pengajuan cuti (own/team/all)
        $leaves = LeaveRequest::with('employee')
            ->when(! $user->can('leave.manage'), function ($q) use ($user) {
                if ($user->hasRole('manager') && $user->employee) {
                    $q->whereHas('employee', fn ($s) => $s->where('manager_id', $user->employee->id));
                } else {
                    $q->where('employee_id', $user->employee?->id ?? 0);
                }
            })
            ->where(fn ($q) => $q->where('reason', 'like', $like)
                ->orWhereHas('employee', fn ($s) => $s->where('full_name', 'like', $like)))
            ->latest()
            ->limit(5)
            ->get();

        foreach ($leaves as $leave) {
            $results[] = [
                'type' => 'Cuti',
                'label' => ($leave->employee->full_name ?? 'Karyawan').' — '.$leave->start_date?->format('d/m/Y'),
                'sublabel' => $leave->status,
                'url' => route('leave.show', $leave),
            ];
        }

        // Lowongan (scope departemen untuk non-manage)
        if ($user->can('recruitment.view')) {
            $postings = JobPosting::query()
                ->when(! $user->can('recruitment.manage'), fn ($q) => $q->where('department_id', $user->employee?->department_id ?? 0))
                ->where('title', 'like', $like)
                ->limit(5)
                ->get();

            foreach ($postings as $posting) {
                $results[] = [
                    'type' => 'Lowongan',
                    'label' => $posting->title,
                    'sublabel' => Str::headline($posting->status),
                    'url' => route('recruitment.applications.index', ['posting_id' => $posting->id]),
                ];
            }
        }

        return response()->json(['data' => $results]);
    }
}
