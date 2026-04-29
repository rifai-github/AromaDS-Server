<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyActivityFormRequest extends FormRequest
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
            'activity_type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'activity_date' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'is_completed' => ['boolean']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'activity_type.required' => 'Tipe aktivitas wajib diisi.',
            'activity_type.max' => 'Tipe aktivitas maksimal 100 karakter.',
            'title.required' => 'Judul aktivitas wajib diisi.',
            'title.max' => 'Judul aktivitas maksimal 255 karakter.',
            'description.required' => 'Deskripsi aktivitas wajib diisi.',
            'description.max' => 'Deskripsi aktivitas maksimal 2000 karakter.',
            'activity_date.required' => 'Tanggal aktivitas wajib diisi.',
            'activity_date.date' => 'Format tanggal aktivitas tidak valid.',
            'duration_minutes.integer' => 'Durasi harus berupa angka.',
            'duration_minutes.min' => 'Durasi minimal 1 menit.',
            'location.max' => 'Lokasi maksimal 255 karakter.',
            'priority.required' => 'Prioritas wajib dipilih.',
            'priority.in' => 'Prioritas tidak valid.',
            'is_completed.boolean' => 'Status selesai harus berupa true atau false.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'activity_type' => 'tipe aktivitas',
            'title' => 'judul aktivitas',
            'description' => 'deskripsi aktivitas',
            'activity_date' => 'tanggal aktivitas',
            'duration_minutes' => 'durasi (menit)',
            'location' => 'lokasi',
            'priority' => 'prioritas',
            'is_completed' => 'status selesai'
        ];
    }
}
