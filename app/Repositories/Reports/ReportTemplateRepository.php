<?php

namespace App\Repositories\Reports;

use App\Models\ReportTemplate;
use App\Models\ReportTemplateField;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportTemplateRepository
{
    /**
     * Get all report templates with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ReportTemplate::with(['fields', 'creator', 'updater']);

        // Apply filters
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['data_source'])) {
            $query->where('data_source', $filters['data_source']);
        }

        if (isset($filters['output_format'])) {
            $query->where('output_format', $filters['output_format']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        return $query->orderBy('name')
                    ->paginate($perPage);
    }

    /**
     * Get report template by ID
     */
    public function getById(int $id): ?ReportTemplate
    {
        return ReportTemplate::with(['fields', 'creator', 'updater'])
                            ->find($id);
    }

    /**
     * Get report template by name
     */
    public function getByName(string $name): ?ReportTemplate
    {
        return ReportTemplate::with(['fields'])
                            ->where('name', $name)
                            ->first();
    }

    /**
     * Get templates by category
     */
    public function getByCategory(string $category): Collection
    {
        return ReportTemplate::with(['fields'])
                            ->where('category', $category)
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get();
    }

    /**
     * Get templates by data source
     */
    public function getByDataSource(string $dataSource): Collection
    {
        return ReportTemplate::with(['fields'])
                            ->where('data_source', $dataSource)
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get();
    }

    /**
     * Get active templates
     */
    public function getActive(): Collection
    {
        return ReportTemplate::with(['fields'])
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get();
    }

    /**
     * Create report template
     */
    public function create(array $data): ReportTemplate
    {
        return ReportTemplate::create($data);
    }

    /**
     * Update report template
     */
    public function update(ReportTemplate $template, array $data): bool
    {
        return $template->update($data);
    }

    /**
     * Delete report template
     */
    public function delete(ReportTemplate $template): bool
    {
        return $template->delete();
    }

    /**
     * Get template fields
     */
    public function getFields(int $templateId): Collection
    {
        return ReportTemplateField::where('template_id', $templateId)
                                 ->orderBy('display_order')
                                 ->get();
    }

    /**
     * Add field to template
     */
    public function addField(int $templateId, array $data): ReportTemplateField
    {
        $data['template_id'] = $templateId;
        return ReportTemplateField::create($data);
    }

    /**
     * Update field
     */
    public function updateField(ReportTemplateField $field, array $data): bool
    {
        return $field->update($data);
    }

    /**
     * Delete field
     */
    public function deleteField(ReportTemplateField $field): bool
    {
        return $field->delete();
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
     * Get data sources
     */
    public function getDataSources(): array
    {
        return ReportTemplate::distinct()
                            ->pluck('data_source')
                            ->filter()
                            ->sort()
                            ->values()
                            ->toArray();
    }

    /**
     * Get output formats
     */
    public function getOutputFormats(): array
    {
        return ReportTemplate::distinct()
                            ->pluck('output_format')
                            ->filter()
                            ->sort()
                            ->values()
                            ->toArray();
    }

    /**
     * Search templates
     */
    public function search(string $search, int $perPage = 15): LengthAwarePaginator
    {
        return ReportTemplate::with(['fields'])
                           ->where(function ($q) use ($search) {
                               $q->where('name', 'like', "%{$search}%")
                                 ->orWhere('description', 'like', "%{$search}%")
                                 ->orWhere('category', 'like', "%{$search}%");
                           })
                           ->orderBy('name')
                           ->paginate($perPage);
    }

    /**
     * Get template statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_templates' => ReportTemplate::count(),
            'active_templates' => ReportTemplate::where('is_active', true)->count(),
            'inactive_templates' => ReportTemplate::where('is_active', false)->count(),
            'templates_by_category' => ReportTemplate::selectRaw('category, COUNT(*) as count')
                                                      ->groupBy('category')
                                                      ->pluck('count', 'category')
                                                      ->toArray(),
            'templates_by_format' => ReportTemplate::selectRaw('output_format, COUNT(*) as count')
                                                   ->groupBy('output_format')
                                                   ->pluck('count', 'output_format')
                                                   ->toArray(),
            'total_fields' => ReportTemplateField::count(),
        ];
    }

    /**
     * Get recent templates
     */
    public function getRecent(int $limit = 5): Collection
    {
        return ReportTemplate::with(['fields'])
                           ->where('is_active', true)
                           ->orderBy('updated_at', 'desc')
                           ->limit($limit)
                           ->get();
    }

    /**
     * Get popular templates
     */
    public function getPopular(int $limit = 5): Collection
    {
        // This would require tracking usage, for now return recent templates
        return $this->getRecent($limit);
    }

    /**
     * Duplicate template
     */
    public function duplicate(ReportTemplate $template, string $newName): ReportTemplate
    {
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
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
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
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]);
        }

        return $newTemplate;
    }

    /**
     * Validate template query
     */
    public function validateQuery(string $query): array
    {
        try {
            // Basic SQL validation
            $query = trim($query);
            
            if (empty($query)) {
                return ['valid' => false, 'error' => 'Query cannot be empty'];
            }

            // Check for dangerous operations
            $dangerousKeywords = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'TRUNCATE'];
            $upperQuery = strtoupper($query);
            
            foreach ($dangerousKeywords as $keyword) {
                if (strpos($upperQuery, $keyword) !== false) {
                    return ['valid' => false, 'error' => "Dangerous keyword '{$keyword}' not allowed"];
                }
            }

            // Check if it starts with SELECT
            if (!preg_match('/^\s*SELECT\s+/i', $query)) {
                return ['valid' => false, 'error' => 'Query must start with SELECT'];
            }

            return ['valid' => true, 'error' => null];

        } catch (\Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test template query
     */
    public function testQuery(string $query, array $parameters = []): array
    {
        try {
            // Replace parameters in query
            foreach ($parameters as $key => $value) {
                $query = str_replace(":{$key}", "'{$value}'", $query);
            }

            // Execute query with limit
            $testQuery = "SELECT * FROM ({$query}) AS test_query LIMIT 1";
            $result = \DB::select($testQuery);

            return [
                'success' => true,
                'data' => $result,
                'error' => null
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage()
            ];
        }
    }
}
