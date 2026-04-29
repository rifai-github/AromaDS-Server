<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyDocumentFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'max:100'],
            'document_name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif'],
            'is_active' => ['boolean']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'document_type.required' => 'Tipe dokumen wajib diisi.',
            'document_type.max' => 'Tipe dokumen maksimal 100 karakter.',
            'document_name.required' => 'Nama dokumen wajib diisi.',
            'document_name.max' => 'Nama dokumen maksimal 255 karakter.',
            'file.required' => 'File dokumen wajib diupload.',
            'file.file' => 'File yang diupload tidak valid.',
            'file.max' => 'Ukuran file maksimal 10MB.',
            'file.mimes' => 'Format file tidak didukung. Gunakan PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, atau GIF.',
            'is_active.boolean' => 'Status aktif harus berupa true atau false.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'document_type' => 'tipe dokumen',
            'document_name' => 'nama dokumen',
            'file' => 'file dokumen',
            'is_active' => 'status aktif'
        ];
    }
}
