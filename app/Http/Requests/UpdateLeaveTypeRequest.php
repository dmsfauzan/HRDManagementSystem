<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('leave.manage');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('leave_types', 'code')->ignore($this->route('type'))],
            'name' => ['required', 'string', 'max:100'],
            'is_paid' => ['boolean'],
            'default_quota' => ['required', 'numeric', 'min:0', 'max:365'],
            'tracks_balance' => ['boolean'],
            'requires_attachment' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode jenis cuti wajib diisi.',
            'code.unique' => 'Kode jenis cuti sudah digunakan.',
            'name.required' => 'Nama jenis cuti wajib diisi.',
            'default_quota.required' => 'Kuota default wajib diisi.',
        ];
    }
}
