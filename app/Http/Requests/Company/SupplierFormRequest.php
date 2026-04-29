<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierFormRequest extends FormRequest
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
        $supplierId = $this->route('supplier') ? $this->route('supplier')->id : null;

        return [
            'company_id' => ['required', 'exists:companies,id'],
            'supplier_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('suppliers', 'supplier_code')->ignore($supplierId)
            ],
            'supplier_name' => ['required', 'string', 'max:255'],
            'supplier_type' => ['required', 'in:individual,company'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($supplierId)
            ],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'subdistrict_id' => ['nullable', 'exists:subdistricts,id'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'company_size' => ['nullable', 'in:startup,small,medium,large,enterprise'],
            'annual_revenue' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'payment_terms' => ['nullable', 'integer', 'min:0']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Perusahaan wajib dipilih.',
            'company_id.exists' => 'Perusahaan tidak valid.',
            'supplier_code.required' => 'Kode supplier wajib diisi.',
            'supplier_code.regex' => 'Kode supplier hanya boleh berisi huruf kapital, angka, underscore, dan dash.',
            'supplier_code.unique' => 'Kode supplier sudah digunakan.',
            'supplier_name.required' => 'Nama supplier wajib diisi.',
            'supplier_name.max' => 'Nama supplier maksimal 255 karakter.',
            'supplier_type.required' => 'Tipe supplier wajib dipilih.',
            'supplier_type.in' => 'Tipe supplier tidak valid.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'address.required' => 'Alamat wajib diisi.',
            'province_id.required' => 'Provinsi wajib dipilih.',
            'province_id.exists' => 'Provinsi tidak valid.',
            'city_id.required' => 'Kota wajib dipilih.',
            'city_id.exists' => 'Kota tidak valid.',
            'district_id.exists' => 'Kecamatan tidak valid.',
            'subdistrict_id.exists' => 'Kelurahan tidak valid.',
            'website.url' => 'Format website tidak valid.',
            'company_size.in' => 'Ukuran perusahaan tidak valid.',
            'annual_revenue.numeric' => 'Pendapatan tahunan harus berupa angka.',
            'annual_revenue.min' => 'Pendapatan tahunan tidak boleh negatif.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
            'credit_limit.numeric' => 'Batas kredit harus berupa angka.',
            'credit_limit.min' => 'Batas kredit tidak boleh negatif.',
            'payment_terms.integer' => 'Syarat pembayaran harus berupa angka.',
            'payment_terms.min' => 'Syarat pembayaran tidak boleh negatif.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'company_id' => 'perusahaan',
            'supplier_code' => 'kode supplier',
            'supplier_name' => 'nama supplier',
            'supplier_type' => 'tipe supplier',
            'contact_person' => 'kontak person',
            'email' => 'email',
            'phone' => 'nomor telepon',
            'address' => 'alamat',
            'province_id' => 'provinsi',
            'city_id' => 'kota',
            'district_id' => 'kecamatan',
            'subdistrict_id' => 'kelurahan',
            'postal_code' => 'kode pos',
            'tax_number' => 'nomor pajak',
            'website' => 'website',
            'industry' => 'industri',
            'company_size' => 'ukuran perusahaan',
            'annual_revenue' => 'pendapatan tahunan',
            'source' => 'sumber',
            'status' => 'status',
            'notes' => 'catatan',
            'credit_limit' => 'batas kredit',
            'currency' => 'mata uang',
            'payment_terms' => 'syarat pembayaran'
        ];
    }
}
