<?php

namespace App\Http\Requests;

use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AttendanceCorrection::class);
    }

    public function rules(): array
    {
        return [
            'work_date' => ['required', 'date', 'before_or_equal:today'],
            'requested_check_in_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'requested_check_out_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'after:requested_check_in_at'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'work_date.required' => 'Tanggal absensi wajib diisi.',
            'work_date.before_or_equal' => 'Tanggal absensi tidak boleh di masa depan.',
            'requested_check_out_at.after' => 'Jam keluar harus setelah jam masuk.',
            'reason.required' => 'Alasan koreksi wajib diisi.',
        ];
    }
}
