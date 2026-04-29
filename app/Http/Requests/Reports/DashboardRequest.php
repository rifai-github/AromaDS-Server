<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
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
            'description' => 'nullable|string|max:1000',
            'layout_config' => 'nullable|string',
            'is_public' => 'boolean',
            'is_default' => 'boolean',
        ];

        // Add specific rules for update
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['name'] = 'required|string|max:255|unique:dashboards,name,' . $this->route('dashboard');
        } else {
            $rules['name'] = 'required|string|max:255|unique:dashboards,name';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Dashboard name is required.',
            'name.unique' => 'Dashboard name already exists.',
            'name.max' => 'Dashboard name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'is_public.boolean' => 'Public flag must be true or false.',
            'is_default.boolean' => 'Default flag must be true or false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'dashboard name',
            'description' => 'description',
            'layout_config' => 'layout configuration',
            'is_public' => 'public access',
            'is_default' => 'default dashboard',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure boolean values are properly cast
        if ($this->has('is_public')) {
            $this->merge([
                'is_public' => filter_var($this->is_public, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        if ($this->has('is_default')) {
            $this->merge([
                'is_default' => filter_var($this->is_default, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        // Ensure layout_config is valid JSON
        if ($this->has('layout_config') && !empty($this->layout_config)) {
            $config = $this->layout_config;
            if (is_string($config)) {
                json_decode($config);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->merge(['layout_config' => '[]']);
                }
            }
        }
    }
}
