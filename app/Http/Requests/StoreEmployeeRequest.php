<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employees.manage');
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_id')],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('employees', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'work_schedule_id' => ['nullable', 'exists:work_schedules,id'],
            'join_date' => ['required', 'date'],
            'employment_type' => ['required', Rule::in(['permanent', 'contract', 'intern', 'probation'])],
            'employment_status' => ['required', Rule::in(['active', 'inactive', 'resigned'])],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'address' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'create_account' => ['boolean'],
            'account_email' => ['required_if:create_account,true', 'nullable', 'email', 'max:150', Rule::unique('users', 'email')],
            'account_password' => ['required_if:create_account,true', 'nullable', 'string', 'min:8'],
            'account_role' => ['required_if:create_account,true', 'nullable', Rule::in(['manager', 'karyawan'])],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee ID wajib diisi.',
            'employee_id.unique' => 'Employee ID sudah digunakan.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.unique' => 'Email karyawan sudah digunakan.',
            'join_date.required' => 'Tanggal mulai bekerja wajib diisi.',
            'employment_type.required' => 'Tipe kepegawaian wajib dipilih.',
            'employment_status.required' => 'Status kepegawaian wajib dipilih.',
            'photo.image' => 'Foto profil harus berupa gambar.',
            'photo.mimes' => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'account_email.required_if' => 'Email akun wajib diisi saat membuat akun login.',
            'account_email.unique' => 'Email akun sudah digunakan.',
            'account_password.required_if' => 'Password akun wajib diisi saat membuat akun login.',
            'account_password.min' => 'Password akun minimal 8 karakter.',
            'account_role.required_if' => 'Role akun wajib dipilih saat membuat akun login.',
        ];
    }
}
