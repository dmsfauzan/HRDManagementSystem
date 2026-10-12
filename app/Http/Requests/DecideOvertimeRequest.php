<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('decide', $this->route('overtime'));
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_note' => ['required_if:action,rejected', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Keputusan wajib dipilih.',
            'decision_note.required_if' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
