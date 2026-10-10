<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()->employee;
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'birth_date' => ['nullable', 'date', 'after_or_equal:1950-01-01', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
        ];
    }

    public function messages(): array
    {
        return [
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
        ];
    }
}
