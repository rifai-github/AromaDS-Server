<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VirtualAccountImportRequest extends FormRequest
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
        $rules = [
            'import_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('virtual_account_imports', 'import_number')->ignore($this->virtual_account_import),
            ],
            'import_date' => 'required|date',
            'bank_id' => 'required|exists:banks,id',
            'file_type' => 'required|in:csv,xlsx,txt',
            'skip_header' => 'boolean',
            'delimiter' => 'nullable|string|max:10',
            'encoding' => 'nullable|string|max:20',
            'va_number_column' => 'required|string|max:255',
            'customer_name_column' => 'required|string|max:255',
            'amount_column' => 'required|string|max:255',
            'due_date_column' => 'nullable|string|max:255',
            'status' => 'required|in:pending,processing,completed,failed',
            'auto_process' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ];

        // Add file validation for create and update with file
        if ($this->isMethod('POST') || $this->hasFile('import_file')) {
            $rules['import_file'] = [
                'required',
                'file',
                'mimes:csv,xlsx,txt',
                'max:10240', // 10MB
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'import_number.required' => 'Import number is required.',
            'import_number.unique' => 'This import number already exists.',
            'import_date.required' => 'Import date is required.',
            'import_date.date' => 'Import date must be a valid date.',
            'bank_id.required' => 'Bank selection is required.',
            'bank_id.exists' => 'Selected bank does not exist.',
            'file_type.required' => 'File type is required.',
            'file_type.in' => 'File type must be CSV, XLSX, or TXT.',
            'import_file.required' => 'Import file is required.',
            'import_file.file' => 'The uploaded file is invalid.',
            'import_file.mimes' => 'File must be CSV, XLSX, or TXT format.',
            'import_file.max' => 'File size must not exceed 10MB.',
            'va_number_column.required' => 'Virtual Account Number column mapping is required.',
            'customer_name_column.required' => 'Customer Name column mapping is required.',
            'amount_column.required' => 'Amount column mapping is required.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be pending, processing, completed, or failed.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'import_number' => 'import number',
            'import_date' => 'import date',
            'bank_id' => 'bank',
            'file_type' => 'file type',
            'import_file' => 'import file',
            'va_number_column' => 'virtual account number column',
            'customer_name_column' => 'customer name column',
            'amount_column' => 'amount column',
            'due_date_column' => 'due date column',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox values to boolean
        $this->merge([
            'skip_header' => $this->has('skip_header'),
            'auto_process' => $this->has('auto_process'),
        ]);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator);
    }
}
