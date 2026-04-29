<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankReceiptRequest extends FormRequest
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
            'receipt_date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'invoice_reference' => 'nullable|string|max:255',
            'bank_id' => 'required|exists:banks,id',
            'account_number' => 'required|string|max:255',
            'account_holder_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:transfer,cash,check,giro',
            'status' => 'required|in:pending,verified,rejected,processed',
            'receipt_image' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120', // 5MB max
            'notes' => 'nullable|string|max:1000',
        ];

        // Add unique validation for receipt_number
        if ($this->isMethod('POST')) {
            $rules['receipt_number'] = 'required|string|max:255|unique:bank_receipts,receipt_number';
        } else {
            $rules['receipt_number'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('bank_receipts', 'receipt_number')->ignore($this->route('bank_receipt')),
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
            'receipt_number.required' => 'Receipt number is required.',
            'receipt_number.unique' => 'This receipt number already exists.',
            'receipt_date.required' => 'Receipt date is required.',
            'receipt_date.date' => 'Receipt date must be a valid date.',
            'customer_id.required' => 'Customer is required.',
            'customer_id.exists' => 'Selected customer does not exist.',
            'bank_id.required' => 'Bank is required.',
            'bank_id.exists' => 'Selected bank does not exist.',
            'account_number.required' => 'Account number is required.',
            'account_holder_name.required' => 'Account holder name is required.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount must be greater than 0.',
            'payment_date.required' => 'Payment date is required.',
            'payment_date.date' => 'Payment date must be a valid date.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method selected.',
            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status selected.',
            'receipt_image.file' => 'Receipt image must be a file.',
            'receipt_image.mimes' => 'Receipt image must be a JPEG, PNG, JPG, or PDF file.',
            'receipt_image.max' => 'Receipt image must not exceed 5MB.',
            'notes.max' => 'Notes must not exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'receipt_number' => 'receipt number',
            'receipt_date' => 'receipt date',
            'customer_id' => 'customer',
            'invoice_reference' => 'invoice reference',
            'bank_id' => 'bank',
            'account_number' => 'account number',
            'account_holder_name' => 'account holder name',
            'amount' => 'amount',
            'payment_date' => 'payment date',
            'payment_method' => 'payment method',
            'status' => 'status',
            'receipt_image' => 'receipt image',
            'notes' => 'notes',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up receipt number
        if ($this->has('receipt_number')) {
            $this->merge([
                'receipt_number' => trim($this->receipt_number)
            ]);
        }

        // Clean up account number
        if ($this->has('account_number')) {
            $this->merge([
                'account_number' => trim($this->account_number)
            ]);
        }

        // Clean up account holder name
        if ($this->has('account_holder_name')) {
            $this->merge([
                'account_holder_name' => trim($this->account_holder_name)
            ]);
        }

        // Clean up invoice reference
        if ($this->has('invoice_reference')) {
            $this->merge([
                'invoice_reference' => trim($this->invoice_reference)
            ]);
        }

        // Clean up notes
        if ($this->has('notes')) {
            $this->merge([
                'notes' => trim($this->notes)
            ]);
        }
    }
}
