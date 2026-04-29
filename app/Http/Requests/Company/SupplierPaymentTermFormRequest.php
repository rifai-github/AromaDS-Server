<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class SupplierPaymentTermFormRequest extends FormRequest
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
            'payment_terms' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'max:3'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['required', 'date', 'after:valid_from'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'payment_terms.required' => 'Syarat pembayaran wajib diisi.',
            'payment_terms.integer' => 'Syarat pembayaran harus berupa angka bulat.',
            'payment_terms.min' => 'Syarat pembayaran tidak boleh negatif.',
            'currency.required' => 'Mata uang wajib diisi.',
            'currency.max' => 'Mata uang maksimal 3 karakter.',
            'valid_from.required' => 'Tanggal mulai berlaku wajib diisi.',
            'valid_from.date' => 'Format tanggal mulai berlaku tidak valid.',
            'valid_to.required' => 'Tanggal berakhir berlaku wajib diisi.',
            'valid_to.date' => 'Format tanggal berakhir berlaku tidak valid.',
            'valid_to.after' => 'Tanggal berakhir berlaku harus setelah tanggal mulai berlaku.',
            'is_active.boolean' => 'Status aktif harus berupa true atau false.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'payment_terms' => 'syarat pembayaran',
            'currency' => 'mata uang',
            'valid_from' => 'tanggal mulai berlaku',
            'valid_to' => 'tanggal berakhir berlaku',
            'notes' => 'catatan',
            'is_active' => 'status aktif'
        ];
    }
}
