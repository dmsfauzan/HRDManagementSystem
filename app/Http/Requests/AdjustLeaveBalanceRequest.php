<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustLeaveBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('leave.balances_manage');
    }

    public function rules(): array
    {
        return [
            'balance' => ['required', 'numeric', 'min:0', 'max:365'],
            'description' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'balance.required' => 'Saldo baru wajib diisi.',
            'balance.numeric' => 'Saldo harus berupa angka.',
            'balance.min' => 'Saldo tidak boleh negatif.',
            'description.required' => 'Keterangan penyesuaian wajib diisi.',
        ];
    }
}
