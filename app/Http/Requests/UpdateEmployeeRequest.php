<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employees.manage');
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'employee_id' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_id')->ignore($employee)],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('employees', 'email')->ignore($employee)],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'manager_id' => ['nullable', 'exists:employees,id', Rule::notIn([$employee?->id])],
            'work_schedule_id' => ['nullable', 'exists:work_schedules,id'],
            'join_date' => ['required', 'date'],
            'employment_type' => ['required', Rule::in(['permanent', 'contract', 'intern', 'probation'])],
            'employment_status' => ['required', Rule::in(['active', 'inactive', 'resigned'])],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'address' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee ID wajib diisi.',
            'employee_id.unique' => 'Employee ID sudah digunakan.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.unique' => 'Email karyawan sudah digunakan.',
            'manager_id.not_in' => 'Karyawan tidak dapat menjadi atasan dirinya sendiri.',
            'join_date.required' => 'Tanggal mulai bekerja wajib diisi.',
            'photo.image' => 'Foto profil harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}
