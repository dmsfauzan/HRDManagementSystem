<?php

namespace App\Http\Requests;

use App\Models\Overtime;
use Illuminate\Foundation\Http\FormRequest;

class StoreOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Overtime::class);
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'start_at' => ['required', 'date_format:H:i'],
            'end_at' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal lembur wajib diisi.',
            'date.before_or_equal' => 'Tanggal lembur tidak boleh di masa depan.',
            'start_at.required' => 'Jam mulai wajib diisi.',
            'end_at.required' => 'Jam selesai wajib diisi.',
            'reason.required' => 'Alasan lembur wajib diisi.',
        ];
    }
}
