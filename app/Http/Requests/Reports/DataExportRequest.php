<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class DataExportRequest extends FormRequest
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
            'template_id' => 'nullable|exists:report_templates,id',
            'data_source' => 'required|string|max:100',
            'query' => 'required|string',
            'parameters' => 'nullable|string',
            'export_format' => 'required|string|in:csv,xlsx,json,pdf',
            'scheduled_at' => 'nullable|date|after:now',
        ];

        // Add specific rules for update
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['name'] = 'required|string|max:255|unique:data_exports,name,' . $this->route('export');
        } else {
            $rules['name'] = 'required|string|max:255|unique:data_exports,name';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Export name is required.',
            'name.unique' => 'Export name already exists.',
            'name.max' => 'Export name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'template_id.exists' => 'Selected template does not exist.',
            'data_source.required' => 'Data source is required.',
            'data_source.max' => 'Data source cannot exceed 100 characters.',
            'query.required' => 'Query is required.',
            'export_format.required' => 'Export format is required.',
            'export_format.in' => 'Export format must be one of: csv, xlsx, json, pdf.',
            'scheduled_at.date' => 'Scheduled date must be a valid date.',
            'scheduled_at.after' => 'Scheduled date must be in the future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'export name',
            'description' => 'description',
            'template_id' => 'template',
            'data_source' => 'data source',
            'query' => 'SQL query',
            'parameters' => 'parameters',
            'export_format' => 'export format',
            'scheduled_at' => 'scheduled date',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
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

        // Convert scheduled_at to Carbon instance if provided
        if ($this->has('scheduled_at') && !empty($this->scheduled_at)) {
            try {
                $this->merge([
                    'scheduled_at' => \Carbon\Carbon::parse($this->scheduled_at)
                ]);
            } catch (\Exception $e) {
                // Let the date validation handle invalid dates
            }
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

            // Validate template_id if provided
            if ($this->has('template_id') && !empty($this->template_id)) {
                $template = \App\Models\ReportTemplate::find($this->template_id);
                if (!$template || !$template->is_active) {
                    $validator->errors()->add('template_id', 'Selected template is not active or does not exist.');
                }
            }
        });
    }
}
