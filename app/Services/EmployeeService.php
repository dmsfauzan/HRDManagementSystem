<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployeeService
{
    public function create(array $data, ?UploadedFile $photo = null): Employee
    {
        return DB::transaction(function () use ($data, $photo) {
            $user = null;

            if (! empty($data['create_account'])) {
                $user = User::create([
                    'name' => $data['full_name'],
                    'email' => $data['account_email'],
                    'password' => Hash::make($data['account_password']),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
                $user->assignRole($data['account_role'] ?? 'karyawan');
            }

            $employee = Employee::create([
                'user_id' => $user?->id,
                'employee_id' => $data['employee_id'],
                'full_name' => $data['full_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'position_id' => $data['position_id'] ?? null,
                'manager_id' => $data['manager_id'] ?? null,
                'work_schedule_id' => $data['work_schedule_id'] ?? null,
                'join_date' => $data['join_date'],
                'employment_type' => $data['employment_type'] ?? 'permanent',
                'employment_status' => $data['employment_status'] ?? 'active',
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'address' => $data['address'] ?? null,
                'photo_path' => null,
            ]);

            if ($photo) {
                $employee->photo_path = $this->storePhoto($employee, $photo);
                $employee->save();
            }

            $year = (int) now('Asia/Jakarta')->format('Y');
            app(LeaveService::class)->ensureBalances($employee, $year);

            AuditLogger::log('employee.created', $employee, [], $this->snapshot($employee));

            return $employee;
        });
    }

    public function update(Employee $employee, array $data, ?UploadedFile $photo = null): Employee
    {
        return DB::transaction(function () use ($employee, $data, $photo) {
            $before = $this->snapshot($employee);

            $employee->fill([
                'employee_id' => $data['employee_id'],
                'full_name' => $data['full_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'position_id' => $data['position_id'] ?? null,
                'manager_id' => $data['manager_id'] ?? null,
                'work_schedule_id' => $data['work_schedule_id'] ?? null,
                'join_date' => $data['join_date'],
                'employment_type' => $data['employment_type'] ?? $employee->employment_type,
                'employment_status' => $data['employment_status'] ?? $employee->employment_status,
                'birth_date' => $data['birth_date'] ?? $employee->birth_date,
                'gender' => $data['gender'] ?? $employee->gender,
                'address' => $data['address'] ?? $employee->address,
            ]);
            $employee->save();

            if ($photo) {
                $this->deletePhoto($employee);
                $employee->photo_path = $this->storePhoto($employee, $photo);
                $employee->save();
            }

            AuditLogger::log('employee.updated', $employee, $before, $this->snapshot($employee));

            return $employee;
        });
    }

    public function deactivate(Employee $employee): Employee
    {
        return DB::transaction(function () use ($employee) {
            $before = $this->snapshot($employee);

            $employee->employment_status = 'inactive';
            $employee->save();

            if ($employee->user) {
                $employee->user->is_active = false;
                $employee->user->save();
            }

            AuditLogger::log('employee.deactivated', $employee, $before, $this->snapshot($employee));

            return $employee;
        });
    }

    public function activate(Employee $employee): Employee
    {
        return DB::transaction(function () use ($employee) {
            $before = $this->snapshot($employee);

            $employee->employment_status = 'active';
            $employee->save();

            if ($employee->user) {
                $employee->user->is_active = true;
                $employee->user->save();
            }

            AuditLogger::log('employee.activated', $employee, $before, $this->snapshot($employee));

            return $employee;
        });
    }

    private function snapshot(Employee $employee): array
    {
        return $employee->only([
            'employee_id', 'full_name', 'email', 'department_id', 'position_id',
            'manager_id', 'work_schedule_id', 'join_date', 'employment_type', 'employment_status',
        ]);
    }

    public function storePhoto(Employee $employee, UploadedFile $photo): string
    {
        $ext = $photo->getClientOriginalExtension() ?: 'jpg';
        $path = sprintf('employees/%d/photo.%s', $employee->id, $ext);

        Storage::disk('private')->put($path, file_get_contents($photo->getRealPath()));

        return $path;
    }

    public function deletePhoto(Employee $employee): void
    {
        if ($employee->photo_path && Storage::disk('private')->exists($employee->photo_path)) {
            Storage::disk('private')->delete($employee->photo_path);
        }
    }
}
