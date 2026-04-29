<?php

namespace App\Services\Reports;

use App\Models\ReportTemplate;
use App\Models\ReportTemplateField;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportTemplateService
{
    /**
     * Create a new report template
     */
    public function createTemplate(array $data): ReportTemplate
    {
        return DB::transaction(function () use ($data) {
            $template = ReportTemplate::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'],
                'data_source' => $data['data_source'],
                'query' => $data['query'],
                'parameters' => $data['parameters'] ?? '{}',
                'output_format' => $data['output_format'] ?? 'pdf',
                'template_config' => $data['template_config'] ?? '{}',
                'is_active' => $data['is_active'] ?? true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Add fields if provided
            if (isset($data['fields'])) {
                $this->addFields($template, $data['fields']);
            }

            return $template->load('fields');
        });
    }

    /**
     * Update report template
     */
    public function updateTemplate(ReportTemplate $template, array $data): ReportTemplate
    {
        return DB::transaction(function () use ($template, $data) {
            $template->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? $template->description,
                'category' => $data['category'] ?? $template->category,
                'data_source' => $data['data_source'] ?? $template->data_source,
                'query' => $data['query'] ?? $template->query,
                'parameters' => $data['parameters'] ?? $template->parameters,
                'output_format' => $data['output_format'] ?? $template->output_format,
                'template_config' => $data['template_config'] ?? $template->template_config,
                'is_active' => $data['is_active'] ?? $template->is_active,
                'updated_by' => Auth::id()
            ]);

            // Update fields if provided
            if (isset($data['fields'])) {
                $this->updateFields($template, $data['fields']);
            }

            return $template->load('fields');
        });
    }

    /**
     * Add fields to template
     */
    public function addFields(ReportTemplate $template, array $fields): void
    {
        foreach ($fields as $field) {
            ReportTemplateField::create([
                'template_id' => $template->id,
                'field_name' => $field['field_name'],
                'field_label' => $field['field_label'],
                'field_type' => $field['field_type'],
                'field_config' => $field['field_config'] ?? '{}',
                'is_required' => $field['is_required'] ?? false,
                'display_order' => $field['display_order'] ?? 0,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);
        }
    }

    /**
     * Update template fields
     */
    public function updateFields(ReportTemplate $template, array $fields): void
    {
        // Remove existing fields
        $template->fields()->delete();

        // Add new fields
        $this->addFields($template, $fields);
    }

    /**
     * Add single field to template
     */
    public function addField(ReportTemplate $template, array $data): ReportTemplateField
    {
        return ReportTemplateField::create([
            'template_id' => $template->id,
            'field_name' => $data['field_name'],
            'field_label' => $data['field_label'],
            'field_type' => $data['field_type'],
            'field_config' => $data['field_config'] ?? '{}',
            'is_required' => $data['is_required'] ?? false,
            'display_order' => $data['display_order'] ?? 0,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);
    }

    /**
     * Update single field
     */
    public function updateField(ReportTemplateField $field, array $data): ReportTemplateField
    {
        $field->update([
            'field_name' => $data['field_name'] ?? $field->field_name,
            'field_label' => $data['field_label'] ?? $field->field_label,
            'field_type' => $data['field_type'] ?? $field->field_type,
            'field_config' => $data['field_config'] ?? $field->field_config,
            'is_required' => $data['is_required'] ?? $field->is_required,
            'display_order' => $data['display_order'] ?? $field->display_order,
            'updated_by' => Auth::id()
        ]);

        return $field;
    }

    /**
     * Remove field from template
     */
    public function removeField(ReportTemplateField $field): bool
    {
        return $field->delete();
    }

    /**
     * Duplicate template
     */
    public function duplicateTemplate(ReportTemplate $template, string $newName): ReportTemplate
    {
        return DB::transaction(function () use ($template, $newName) {
            $newTemplate = ReportTemplate::create([
                'name' => $newName,
                'description' => $template->description,
                'category' => $template->category,
                'data_source' => $template->data_source,
                'query' => $template->query,
                'parameters' => $template->parameters,
                'output_format' => $template->output_format,
                'template_config' => $template->template_config,
                'is_active' => $template->is_active,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Duplicate fields
            foreach ($template->fields as $field) {
                ReportTemplateField::create([
                    'template_id' => $newTemplate->id,
                    'field_name' => $field->field_name,
                    'field_label' => $field->field_label,
                    'field_type' => $field->field_type,
                    'field_config' => $field->field_config,
                    'is_required' => $field->is_required,
                    'display_order' => $field->display_order,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            return $newTemplate->load('fields');
        });
    }

    /**
     * Generate report from template
     */
    public function generateReport(ReportTemplate $template, array $parameters = []): array
    {
        // Validate parameters
        $this->validateParameters($template, $parameters);

        // Execute query with parameters
        $data = $this->executeQuery($template, $parameters);

        // Format data according to template
        $formattedData = $this->formatData($template, $data);

        return [
            'template' => $template,
            'parameters' => $parameters,
            'data' => $formattedData,
            'generated_at' => now()
        ];
    }

    /**
     * Validate parameters against template
     */
    private function validateParameters(ReportTemplate $template, array $parameters): void
    {
        $requiredFields = $template->fields()->where('is_required', true)->get();

        foreach ($requiredFields as $field) {
            if (!isset($parameters[$field->field_name]) || empty($parameters[$field->field_name])) {
                throw new \InvalidArgumentException("Required parameter '{$field->field_name}' is missing");
            }
        }
    }

    /**
     * Execute query with parameters
     */
    private function executeQuery(ReportTemplate $template, array $parameters): array
    {
        $query = $template->query;

        // Replace parameters in query
        foreach ($parameters as $key => $value) {
            $query = str_replace(":{$key}", "'{$value}'", $query);
        }

        try {
            return DB::select($query);
        } catch (\Exception $e) {
            throw new \RuntimeException("Query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Format data according to template configuration
     */
    private function formatData(ReportTemplate $template, array $data): array
    {
        $config = json_decode($template->template_config, true) ?? [];
        $formattedData = [];

        foreach ($data as $row) {
            $formattedRow = [];
            foreach ($row as $key => $value) {
                // Apply formatting based on field configuration
                $field = $template->fields()->where('field_name', $key)->first();
                if ($field) {
                    $fieldConfig = json_decode($field->field_config, true) ?? [];
                    $formattedRow[$key] = $this->formatFieldValue($value, $fieldConfig);
                } else {
                    $formattedRow[$key] = $value;
                }
            }
            $formattedData[] = $formattedRow;
        }

        return $formattedData;
    }

    /**
     * Format field value based on configuration
     */
    private function formatFieldValue($value, array $config): string
    {
        if (isset($config['format'])) {
            switch ($config['format']) {
                case 'currency':
                    return number_format($value, 2);
                case 'percentage':
                    return number_format($value, 2) . '%';
                case 'date':
                    return date('Y-m-d', strtotime($value));
                case 'datetime':
                    return date('Y-m-d H:i:s', strtotime($value));
                default:
                    return $value;
            }
        }

        return $value;
    }

    /**
     * Get template categories
     */
    public function getCategories(): array
    {
        return ReportTemplate::distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get templates by category
     */
    public function getTemplatesByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return ReportTemplate::where('category', $category)
            ->where('is_active', true)
            ->with('fields')
            ->orderBy('name')
            ->get();
    }
}
