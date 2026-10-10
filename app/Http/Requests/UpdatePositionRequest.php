<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('positions.manage');
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:30', 'alpha_dash',
                Rule::unique('positions', 'code')->ignore($this->route('position')),
            ],
            'name' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'level' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode jabatan wajib diisi.',
            'code.unique' => 'Kode jabatan sudah digunakan.',
            'name.required' => 'Nama jabatan wajib diisi.',
            'department_id.exists' => 'Departemen yang dipilih tidak valid.',
        ];
    }
}
