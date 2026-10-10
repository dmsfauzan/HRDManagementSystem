<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('documents.manage');
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:50'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'title.required' => 'Judul dokumen wajib diisi.',
            'file.required' => 'Berkas wajib diunggah.',
            'file.mimes' => 'Format berkas harus PDF, DOC/DOCX, atau gambar.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
        ];
    }
}
