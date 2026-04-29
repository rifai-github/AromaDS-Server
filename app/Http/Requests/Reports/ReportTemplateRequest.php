<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class ReportTemplateRequest extends FormRequest
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
            'category' => 'required|string|max:100',
            'data_source' => 'required|string|max:100',
            'query' => 'required|string',
            'parameters' => 'nullable|string',
            'output_format' => 'required|string|in:pdf,excel,csv,json',
            'template_config' => 'nullable|string',
            'is_active' => 'boolean',
            'fields' => 'nullable|array',
            'fields.*.field_name' => 'required_with:fields|string|max:100',
            'fields.*.field_label' => 'required_with:fields|string|max:255',
            'fields.*.field_type' => 'required_with:fields|string|in:text,number,date,select,checkbox,textarea',
            'fields.*.field_config' => 'nullable|string',
            'fields.*.is_required' => 'boolean',
            'fields.*.display_order' => 'integer|min:0',
        ];

        // Add specific rules for update
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['name'] = 'required|string|max:255|unique:report_templates,name,' . $this->route('template');
        } else {
            $rules['name'] = 'required|string|max:255|unique:report_templates,name';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Template name is required.',
            'name.unique' => 'Template name already exists.',
            'name.max' => 'Template name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'category.required' => 'Category is required.',
            'category.max' => 'Category cannot exceed 100 characters.',
            'data_source.required' => 'Data source is required.',
            'data_source.max' => 'Data source cannot exceed 100 characters.',
            'query.required' => 'Query is required.',
            'output_format.required' => 'Output format is required.',
            'output_format.in' => 'Output format must be one of: pdf, excel, csv, json.',
            'is_active.boolean' => 'Active flag must be true or false.',
            'fields.array' => 'Fields must be an array.',
            'fields.*.field_name.required_with' => 'Field name is required when fields are provided.',
            'fields.*.field_label.required_with' => 'Field label is required when fields are provided.',
            'fields.*.field_type.required_with' => 'Field type is required when fields are provided.',
            'fields.*.field_type.in' => 'Field type must be one of: text, number, date, select, checkbox, textarea.',
            'fields.*.is_required.boolean' => 'Field required flag must be true or false.',
            'fields.*.display_order.integer' => 'Display order must be an integer.',
            'fields.*.display_order.min' => 'Display order must be at least 0.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'template name',
            'description' => 'description',
            'category' => 'category',
            'data_source' => 'data source',
            'query' => 'SQL query',
            'parameters' => 'parameters',
            'output_format' => 'output format',
            'template_config' => 'template configuration',
            'is_active' => 'active status',
            'fields' => 'template fields',
            'fields.*.field_name' => 'field name',
            'fields.*.field_label' => 'field label',
            'fields.*.field_type' => 'field type',
            'fields.*.field_config' => 'field configuration',
            'fields.*.is_required' => 'required field',
            'fields.*.display_order' => 'display order',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure boolean values are properly cast
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        // Ensure parameters is valid JSON
        if ($this->has('parameters') && !empty($this->parameters)) {
            $parameters = $this->parameters;
            if (is_string($parameters)) {
                json_decode($parameters);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->merge(['parameters' => '{}']);
                }
            }
        }

        // Ensure template_config is valid JSON
        if ($this->has('template_config') && !empty($this->template_config)) {
            $config = $this->template_config;
            if (is_string($config)) {
                json_decode($config);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->merge(['template_config' => '{}']);
                }
            }
        }

        // Process fields array
        if ($this->has('fields') && is_array($this->fields)) {
            $processedFields = [];
            foreach ($this->fields as $index => $field) {
                $processedField = $field;
                
                // Ensure field_config is valid JSON
                if (isset($field['field_config']) && !empty($field['field_config'])) {
                    if (is_string($field['field_config'])) {
                        json_decode($field['field_config']);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $processedField['field_config'] = '{}';
                        }
                    }
                }

                // Ensure boolean values
                if (isset($field['is_required'])) {
                    $processedField['is_required'] = filter_var($field['is_required'], FILTER_VALIDATE_BOOLEAN);
                }

                // Ensure integer values
                if (isset($field['display_order'])) {
                    $processedField['display_order'] = (int) $field['display_order'];
                }

                $processedFields[$index] = $processedField;
            }
            $this->merge(['fields' => $processedFields]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate SQL query
            if ($this->has('query')) {
                $query = trim($this->query);
                
                if (empty($query)) {
                    $validator->errors()->add('query', 'Query cannot be empty.');
                    return;
                }

                // Check for dangerous operations
                $dangerousKeywords = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'TRUNCATE'];
                $upperQuery = strtoupper($query);
                
                foreach ($dangerousKeywords as $keyword) {
                    if (strpos($upperQuery, $keyword) !== false) {
                        $validator->errors()->add('query', "Dangerous keyword '{$keyword}' is not allowed in queries.");
                        break;
                    }
                }

                // Check if it starts with SELECT
                if (!preg_match('/^\s*SELECT\s+/i', $query)) {
                    $validator->errors()->add('query', 'Query must start with SELECT statement.');
                }
            }
        });
    }
}
