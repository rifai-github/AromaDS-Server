<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanySettingsFormRequest extends FormRequest
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
            'default_currency' => ['required', 'string', 'max:3'],
            'default_language' => ['required', 'string', 'max:5'],
            'timezone' => ['required', 'string', 'max:50'],
            'date_format' => ['required', 'string', 'max:20'],
            'time_format' => ['required', 'string', 'max:10'],
            'number_format' => ['required', 'string', 'max:20'],
            'tax_calculation_method' => ['required', 'in:inclusive,exclusive'],
            'invoice_prefix' => ['required', 'string', 'max:10'],
            'quotation_prefix' => ['required', 'string', 'max:10'],
            'purchase_order_prefix' => ['required', 'string', 'max:10'],
            'receipt_prefix' => ['required', 'string', 'max:10'],
            'payment_prefix' => ['required', 'string', 'max:10'],
            'auto_generate_code' => ['boolean'],
            'code_length' => ['required', 'integer', 'min:3', 'max:10'],
            'send_email_notifications' => ['boolean'],
            'send_sms_notifications' => ['boolean'],
            'allow_negative_stock' => ['boolean'],
            'require_approval_for_purchase' => ['boolean'],
            'require_approval_for_sale' => ['boolean'],
            'default_payment_terms' => ['required', 'integer', 'min:0'],
            'default_credit_limit' => ['required', 'numeric', 'min:0'],
            'auto_close_quotation_days' => ['required', 'integer', 'min:1'],
            'auto_close_invoice_days' => ['required', 'integer', 'min:1'],
            'backup_frequency' => ['required', 'in:daily,weekly,monthly'],
            'data_retention_days' => ['required', 'integer', 'min:30'],
            'is_active' => ['boolean']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'default_currency.required' => 'Mata uang default wajib diisi.',
            'default_currency.max' => 'Mata uang default maksimal 3 karakter.',
            'default_language.required' => 'Bahasa default wajib diisi.',
            'default_language.max' => 'Bahasa default maksimal 5 karakter.',
            'timezone.required' => 'Timezone wajib diisi.',
            'timezone.max' => 'Timezone maksimal 50 karakter.',
            'date_format.required' => 'Format tanggal wajib diisi.',
            'date_format.max' => 'Format tanggal maksimal 20 karakter.',
            'time_format.required' => 'Format waktu wajib diisi.',
            'time_format.max' => 'Format waktu maksimal 10 karakter.',
            'number_format.required' => 'Format angka wajib diisi.',
            'number_format.max' => 'Format angka maksimal 20 karakter.',
            'tax_calculation_method.required' => 'Metode perhitungan pajak wajib dipilih.',
            'tax_calculation_method.in' => 'Metode perhitungan pajak tidak valid.',
            'invoice_prefix.required' => 'Prefix invoice wajib diisi.',
            'invoice_prefix.max' => 'Prefix invoice maksimal 10 karakter.',
            'quotation_prefix.required' => 'Prefix quotation wajib diisi.',
            'quotation_prefix.max' => 'Prefix quotation maksimal 10 karakter.',
            'purchase_order_prefix.required' => 'Prefix purchase order wajib diisi.',
            'purchase_order_prefix.max' => 'Prefix purchase order maksimal 10 karakter.',
            'receipt_prefix.required' => 'Prefix receipt wajib diisi.',
            'receipt_prefix.max' => 'Prefix receipt maksimal 10 karakter.',
            'payment_prefix.required' => 'Prefix payment wajib diisi.',
            'payment_prefix.max' => 'Prefix payment maksimal 10 karakter.',
            'code_length.required' => 'Panjang kode wajib diisi.',
            'code_length.integer' => 'Panjang kode harus berupa angka.',
            'code_length.min' => 'Panjang kode minimal 3 karakter.',
            'code_length.max' => 'Panjang kode maksimal 10 karakter.',
            'default_payment_terms.required' => 'Syarat pembayaran default wajib diisi.',
            'default_payment_terms.integer' => 'Syarat pembayaran default harus berupa angka.',
            'default_payment_terms.min' => 'Syarat pembayaran default tidak boleh negatif.',
            'default_credit_limit.required' => 'Batas kredit default wajib diisi.',
            'default_credit_limit.numeric' => 'Batas kredit default harus berupa angka.',
            'default_credit_limit.min' => 'Batas kredit default tidak boleh negatif.',
            'auto_close_quotation_days.required' => 'Hari auto close quotation wajib diisi.',
            'auto_close_quotation_days.integer' => 'Hari auto close quotation harus berupa angka.',
            'auto_close_quotation_days.min' => 'Hari auto close quotation minimal 1 hari.',
            'auto_close_invoice_days.required' => 'Hari auto close invoice wajib diisi.',
            'auto_close_invoice_days.integer' => 'Hari auto close invoice harus berupa angka.',
            'auto_close_invoice_days.min' => 'Hari auto close invoice minimal 1 hari.',
            'backup_frequency.required' => 'Frekuensi backup wajib dipilih.',
            'backup_frequency.in' => 'Frekuensi backup tidak valid.',
            'data_retention_days.required' => 'Hari retensi data wajib diisi.',
            'data_retention_days.integer' => 'Hari retensi data harus berupa angka.',
            'data_retention_days.min' => 'Hari retensi data minimal 30 hari.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'default_currency' => 'mata uang default',
            'default_language' => 'bahasa default',
            'timezone' => 'timezone',
            'date_format' => 'format tanggal',
            'time_format' => 'format waktu',
            'number_format' => 'format angka',
            'tax_calculation_method' => 'metode perhitungan pajak',
            'invoice_prefix' => 'prefix invoice',
            'quotation_prefix' => 'prefix quotation',
            'purchase_order_prefix' => 'prefix purchase order',
            'receipt_prefix' => 'prefix receipt',
            'payment_prefix' => 'prefix payment',
            'auto_generate_code' => 'auto generate code',
            'code_length' => 'panjang kode',
            'send_email_notifications' => 'kirim notifikasi email',
            'send_sms_notifications' => 'kirim notifikasi SMS',
            'allow_negative_stock' => 'izinkan stok negatif',
            'require_approval_for_purchase' => 'perlu persetujuan untuk pembelian',
            'require_approval_for_sale' => 'perlu persetujuan untuk penjualan',
            'default_payment_terms' => 'syarat pembayaran default',
            'default_credit_limit' => 'batas kredit default',
            'auto_close_quotation_days' => 'hari auto close quotation',
            'auto_close_invoice_days' => 'hari auto close invoice',
            'backup_frequency' => 'frekuensi backup',
            'data_retention_days' => 'hari retensi data',
            'is_active' => 'status aktif'
        ];
    }
}
