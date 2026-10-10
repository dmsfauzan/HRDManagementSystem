<?php

namespace App\Http\Requests;

use App\Models\Holiday;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('leave.manage');
    }

    public function rules(): array
    {
        return [
            'date' => [
                'required', 'date',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (Holiday::whereDate('date', $value)->exists()) {
                        $fail('Tanggal tersebut sudah terdaftar sebagai hari libur.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:150'],
            'is_national' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal wajib diisi.',
            'name.required' => 'Nama hari libur wajib diisi.',
        ];
    }
}
