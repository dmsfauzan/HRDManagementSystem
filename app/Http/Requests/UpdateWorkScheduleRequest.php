<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('attendance.manage');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('work_schedules', 'code')->ignore($this->route('work_schedule'))],
            'name' => ['required', 'string', 'max:100'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['integer', 'between:1,7'],
            'crosses_midnight' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode jadwal wajib diisi.',
            'code.unique' => 'Kode jadwal sudah digunakan.',
            'name.required' => 'Nama jadwal wajib diisi.',
            'check_in_time.required' => 'Jam masuk wajib diisi.',
            'check_out_time.required' => 'Jam keluar wajib diisi.',
            'work_days.required' => 'Pilih minimal satu hari kerja.',
        ];
    }
}
