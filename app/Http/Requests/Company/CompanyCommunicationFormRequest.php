<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyCommunicationFormRequest extends FormRequest
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
            'communication_type' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'communication_date' => ['required', 'date'],
            'direction' => ['required', 'in:inbound,outbound'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'status' => ['required', 'in:unread,read,replied,closed']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'communication_type.required' => 'Tipe komunikasi wajib diisi.',
            'communication_type.max' => 'Tipe komunikasi maksimal 100 karakter.',
            'subject.required' => 'Subjek komunikasi wajib diisi.',
            'subject.max' => 'Subjek komunikasi maksimal 255 karakter.',
            'content.required' => 'Isi komunikasi wajib diisi.',
            'content.max' => 'Isi komunikasi maksimal 5000 karakter.',
            'communication_date.required' => 'Tanggal komunikasi wajib diisi.',
            'communication_date.date' => 'Format tanggal komunikasi tidak valid.',
            'direction.required' => 'Arah komunikasi wajib dipilih.',
            'direction.in' => 'Arah komunikasi tidak valid.',
            'priority.required' => 'Prioritas wajib dipilih.',
            'priority.in' => 'Prioritas tidak valid.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'communication_type' => 'tipe komunikasi',
            'subject' => 'subjek komunikasi',
            'content' => 'isi komunikasi',
            'communication_date' => 'tanggal komunikasi',
            'direction' => 'arah komunikasi',
            'priority' => 'prioritas',
            'status' => 'status'
        ];
    }
}
