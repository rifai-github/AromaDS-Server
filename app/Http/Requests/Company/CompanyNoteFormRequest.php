<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyNoteFormRequest extends FormRequest
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
            'note_type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'is_private' => ['boolean'],
            'is_important' => ['boolean']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'note_type.required' => 'Tipe catatan wajib diisi.',
            'note_type.max' => 'Tipe catatan maksimal 100 karakter.',
            'title.required' => 'Judul catatan wajib diisi.',
            'title.max' => 'Judul catatan maksimal 255 karakter.',
            'content.required' => 'Isi catatan wajib diisi.',
            'content.max' => 'Isi catatan maksimal 5000 karakter.',
            'is_private.boolean' => 'Status private harus berupa true atau false.',
            'is_important.boolean' => 'Status penting harus berupa true atau false.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'note_type' => 'tipe catatan',
            'title' => 'judul catatan',
            'content' => 'isi catatan',
            'is_private' => 'status private',
            'is_important' => 'status penting'
        ];
    }
}
