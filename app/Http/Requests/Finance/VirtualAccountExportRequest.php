<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VirtualAccountExportRequest extends FormRequest
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
            'export_date' => 'required|date',
            'bank_id' => 'required|exists:banks,id',
            'file_type' => 'required|in:csv,xlsx,txt',
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status_filter' => 'nullable|in:active,inactive,expired',
            'limit_records' => 'nullable|integer|min:1|max:10000',
            'include_header' => 'boolean',
            'delimiter' => 'nullable|in:,,;,|,\t',
            'include_columns' => 'required|array|min:1',
            'include_columns.*' => 'in:va_number,customer_name,amount,due_date,status,created_at,updated_at,notes',
            'auto_process' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ];

        // Add unique validation for export_number on create
        if ($this->isMethod('POST')) {
            $rules['export_number'] = 'nullable|string|unique:virtual_account_exports,export_number';
        } else {
            $rules['export_number'] = [
                'nullable',
                'string',
                Rule::unique('virtual_account_exports', 'export_number')->ignore($this->virtual_account_export)
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
            'export_date.required' => 'Export date is required.',
            'export_date.date' => 'Export date must be a valid date.',
            'bank_id.required' => 'Bank selection is required.',
            'bank_id.exists' => 'Selected bank does not exist.',
            'file_type.required' => 'Export format is required.',
            'file_type.in' => 'Export format must be CSV, Excel, or Text.',
            'date_from.date' => 'Date from must be a valid date.',
            'date_from.before_or_equal' => 'Date from must be before or equal to date to.',
            'date_to.date' => 'Date to must be a valid date.',
            'date_to.after_or_equal' => 'Date to must be after or equal to date from.',
            'status_filter.in' => 'Status filter must be active, inactive, or expired.',
            'limit_records.integer' => 'Limit records must be a number.',
            'limit_records.min' => 'Limit records must be at least 1.',
            'limit_records.max' => 'Limit records cannot exceed 10,000.',
            'include_header.boolean' => 'Include header must be true or false.',
            'delimiter.in' => 'Delimiter must be comma, semicolon, pipe, or tab.',
            'include_columns.required' => 'At least one column must be selected.',
            'include_columns.array' => 'Include columns must be an array.',
            'include_columns.min' => 'At least one column must be selected.',
            'include_columns.*.in' => 'Invalid column selected.',
            'auto_process.boolean' => 'Auto process must be true or false.',
            'notes.string' => 'Notes must be text.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'export_number.unique' => 'Export number already exists.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'export_date' => 'export date',
            'bank_id' => 'bank',
            'file_type' => 'export format',
            'date_from' => 'date from',
            'date_to' => 'date to',
            'status_filter' => 'status filter',
            'limit_records' => 'limit records',
            'include_header' => 'include header',
            'delimiter' => 'delimiter',
            'include_columns' => 'include columns',
            'auto_process' => 'auto process',
            'notes' => 'notes',
            'export_number' => 'export number',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure include_columns is always an array
        if ($this->has('include_columns') && !is_array($this->include_columns)) {
            $this->merge([
                'include_columns' => [$this->include_columns]
            ]);
        }

        // Set default values
        $this->merge([
            'include_header' => $this->boolean('include_header'),
            'auto_process' => $this->boolean('auto_process'),
            'limit_records' => $this->limit_records ?? 1000,
            'delimiter' => $this->delimiter ?? ',',
        ]);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        if ($this->expectsJson()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
