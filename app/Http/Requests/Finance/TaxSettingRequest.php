<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxSettingRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'tax_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('tax_settings', 'tax_code')->ignore($this->tax_setting),
            ],
            'tax_type' => 'required|in:income,sales,vat,withholding,other',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:1000',
            'effective_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'status' => 'required|in:active,inactive',
            'is_compound' => 'boolean',
            'calculation_method' => 'nullable|in:percentage,fixed,tiered',
            'rounding_method' => 'nullable|in:nearest,up,down,none',
            'decimal_places' => 'nullable|integer|in:0,2,4',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0|gte:minimum_amount',
            'notes' => 'nullable|string|max:1000',
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tax name is required.',
            'name.max' => 'Tax name cannot exceed 255 characters.',
            
            'tax_code.required' => 'Tax code is required.',
            'tax_code.unique' => 'This tax code is already in use.',
            'tax_code.max' => 'Tax code cannot exceed 20 characters.',
            
            'tax_type.required' => 'Tax type is required.',
            'tax_type.in' => 'Please select a valid tax type.',
            
            'tax_rate.required' => 'Tax rate is required.',
            'tax_rate.numeric' => 'Tax rate must be a number.',
            'tax_rate.min' => 'Tax rate cannot be negative.',
            'tax_rate.max' => 'Tax rate cannot exceed 100%.',
            
            'description.max' => 'Description cannot exceed 1000 characters.',
            
            'effective_date.date' => 'Effective date must be a valid date.',
            
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date must be after or equal to effective date.',
            
            'status.required' => 'Status is required.',
            'status.in' => 'Please select a valid status.',
            
            'is_compound.boolean' => 'Compound tax must be true or false.',
            
            'calculation_method.in' => 'Please select a valid calculation method.',
            
            'rounding_method.in' => 'Please select a valid rounding method.',
            
            'decimal_places.integer' => 'Decimal places must be a whole number.',
            'decimal_places.in' => 'Decimal places must be 0, 2, or 4.',
            
            'minimum_amount.numeric' => 'Minimum amount must be a number.',
            'minimum_amount.min' => 'Minimum amount cannot be negative.',
            
            'maximum_amount.numeric' => 'Maximum amount must be a number.',
            'maximum_amount.min' => 'Maximum amount cannot be negative.',
            'maximum_amount.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tax name',
            'tax_code' => 'tax code',
            'tax_type' => 'tax type',
            'tax_rate' => 'tax rate',
            'description' => 'description',
            'effective_date' => 'effective date',
            'end_date' => 'end date',
            'status' => 'status',
            'is_compound' => 'compound tax',
            'calculation_method' => 'calculation method',
            'rounding_method' => 'rounding method',
            'decimal_places' => 'decimal places',
            'minimum_amount' => 'minimum amount',
            'maximum_amount' => 'maximum amount',
            'notes' => 'notes',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty strings to null for optional fields
        $this->merge([
            'description' => $this->description ?: null,
            'effective_date' => $this->effective_date ?: null,
            'end_date' => $this->end_date ?: null,
            'calculation_method' => $this->calculation_method ?: 'percentage',
            'rounding_method' => $this->rounding_method ?: 'nearest',
            'decimal_places' => $this->decimal_places ?: 2,
            'minimum_amount' => $this->minimum_amount ?: null,
            'maximum_amount' => $this->maximum_amount ?: null,
            'notes' => $this->notes ?: null,
            'is_compound' => $this->boolean('is_compound'),
        ]);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // You can customize the error response here if needed
        parent::failedValidation($validator);
    }

    /**
     * Get additional validation rules based on tax type.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional validation for specific tax types
            if ($this->tax_type === 'income' && $this->tax_rate > 50) {
                $validator->errors()->add('tax_rate', 'Income tax rate cannot exceed 50%.');
            }

            if ($this->tax_type === 'vat' && $this->tax_rate > 20) {
                $validator->errors()->add('tax_rate', 'VAT rate cannot exceed 20%.');
            }

            // Validate date ranges don't overlap with existing active tax settings
            if ($this->effective_date && $this->status === 'active') {
                $query = \App\Models\Finance\TaxSetting::where('status', 'active')
                    ->where('tax_type', $this->tax_type);

                if ($this->tax_setting) {
                    $query->where('id', '!=', $this->tax_setting->id);
                }

                $overlapping = $query->where(function ($q) {
                    $q->whereBetween('effective_date', [$this->effective_date, $this->end_date ?: '9999-12-31'])
                      ->orWhereBetween('end_date', [$this->effective_date, $this->end_date ?: '9999-12-31'])
                      ->orWhere(function ($subQ) {
                          $subQ->where('effective_date', '<=', $this->effective_date)
                               ->where(function ($endQ) {
                                   $endQ->whereNull('end_date')
                                        ->orWhere('end_date', '>=', $this->end_date ?: '9999-12-31');
                               });
                      });
                })->exists();

                if ($overlapping) {
                    $validator->errors()->add('effective_date', 'This date range overlaps with an existing active tax setting of the same type.');
                }
            }

            // Validate calculation method compatibility
            if ($this->calculation_method === 'fixed' && $this->tax_rate <= 0) {
                $validator->errors()->add('tax_rate', 'Fixed amount must be greater than 0.');
            }

            if ($this->calculation_method === 'percentage' && $this->tax_rate > 100) {
                $validator->errors()->add('tax_rate', 'Percentage rate cannot exceed 100%.');
            }
        });
    }
}
