<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyFormRequest extends FormRequest
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
        $companyId = $this->route('company') ? $this->route('company')->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('companies', 'code')->ignore($companyId)
            ],
            'company_type' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'employee_count' => ['nullable', 'integer', 'min:0'],
            'annual_revenue' => ['nullable', 'numeric', 'min:0'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('companies', 'email')->ignore($companyId)
            ],
            'phone' => ['required', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'subdistrict_id' => ['nullable', 'exists:subdistricts,id'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:active,inactive'],
            'company_type' => ['required', 'in:pt,cv,ud,foundation,government,other'],
            'is_pkp' => ['boolean'],
            'is_active' => ['boolean'],
            'default_payment' => ['required', 'in:cash,credit,transfer'],
            'member_since' => ['nullable', 'date'],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'nib_number' => ['nullable', 'string', 'max:50'],
            'grace_period_days' => ['nullable', 'integer', 'min:0'],
            'industry' => ['nullable', 'string', 'max:255'],
            'employee_count' => ['nullable', 'integer', 'min:0'],
            'annual_revenue' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama perusahaan wajib diisi.',
            'name.max' => 'Nama perusahaan maksimal 255 karakter.',
            'code.required' => 'Kode perusahaan wajib diisi.',
            'code.regex' => 'Kode perusahaan hanya boleh berisi huruf kapital, angka, underscore, dan dash.',
            'code.unique' => 'Kode perusahaan sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'website.url' => 'Format website tidak valid.',
            'address.required' => 'Alamat wajib diisi.',
            'province_id.required' => 'Provinsi wajib dipilih.',
            'province_id.exists' => 'Provinsi tidak valid.',
            'city_id.required' => 'Kota wajib dipilih.',
            'city_id.exists' => 'Kota tidak valid.',
            'district_id.exists' => 'Kecamatan tidak valid.',
            'subdistrict_id.exists' => 'Kelurahan tidak valid.',
            'employee_count.integer' => 'Jumlah karyawan harus berupa angka.',
            'employee_count.min' => 'Jumlah karyawan tidak boleh negatif.',
            'annual_revenue.numeric' => 'Pendapatan tahunan harus berupa angka.',
            'annual_revenue.min' => 'Pendapatan tahunan tidak boleh negatif.',
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
            'name' => 'nama perusahaan',
            'code' => 'kode perusahaan',
            'company_type' => 'tipe perusahaan',
            'industry' => 'industri',
            'employee_count' => 'jumlah karyawan',
            'annual_revenue' => 'pendapatan tahunan',
            'email' => 'email',
            'phone' => 'nomor telepon',
            'website' => 'website',
            'tax_number' => 'nomor pajak',
            'address' => 'alamat',
            'province_id' => 'provinsi',
            'city_id' => 'kota',
            'district_id' => 'kecamatan',
            'subdistrict_id' => 'kelurahan',
            'postal_code' => 'kode pos',
            'description' => 'deskripsi',
            'status' => 'status'
        ];
    }
}
