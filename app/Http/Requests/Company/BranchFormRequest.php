<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchFormRequest extends FormRequest
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
        $branchId = $this->route('branch') ? $this->route('branch')->id : null;

        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('branches', 'branch_code')->ignore($branchId)
            ],
            'branch_name' => ['required', 'string', 'max:255'],
            'branch_type' => ['required', 'in:main,branch,warehouse,office'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('branches', 'email')->ignore($branchId)
            ],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'subdistrict_id' => ['nullable', 'exists:subdistricts,id'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'opening_hours' => ['nullable', 'date_format:H:i'],
            'closing_hours' => ['nullable', 'date_format:H:i'],
            'is_24_hours' => ['boolean'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:1000']
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
            'branch_code.required' => 'Kode cabang wajib diisi.',
            'branch_code.regex' => 'Kode cabang hanya boleh berisi huruf kapital, angka, underscore, dan dash.',
            'branch_code.unique' => 'Kode cabang sudah digunakan.',
            'branch_name.required' => 'Nama cabang wajib diisi.',
            'branch_name.max' => 'Nama cabang maksimal 255 karakter.',
            'branch_type.required' => 'Tipe cabang wajib dipilih.',
            'branch_type.in' => 'Tipe cabang tidak valid.',
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
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'opening_hours.date_format' => 'Format jam buka tidak valid (HH:MM).',
            'closing_hours.date_format' => 'Format jam tutup tidak valid (HH:MM).',
            'is_24_hours.boolean' => 'Status 24 jam harus berupa true atau false.',
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
            'company_id' => 'perusahaan',
            'branch_code' => 'kode cabang',
            'branch_name' => 'nama cabang',
            'branch_type' => 'tipe cabang',
            'contact_person' => 'kontak person',
            'email' => 'email',
            'phone' => 'nomor telepon',
            'address' => 'alamat',
            'province_id' => 'provinsi',
            'city_id' => 'kota',
            'district_id' => 'kecamatan',
            'subdistrict_id' => 'kelurahan',
            'postal_code' => 'kode pos',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'opening_hours' => 'jam buka',
            'closing_hours' => 'jam tutup',
            'is_24_hours' => 'buka 24 jam',
            'status' => 'status',
            'notes' => 'catatan'
        ];
    }
}
