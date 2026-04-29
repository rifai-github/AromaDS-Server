<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

trait ColumnFilterTrait
{
    /**
     * Auto-apply filters based on request parameters with 'filter' array.
     * Supports two signatures:
     * 1. applyColumnFilters($query) - Simple auto-filter from request
     * 2. applyColumnFilters($query, $tableId, $columnMap) - Filter with column mapping
     * 
     * Expects URL params like: ?filter[column_name]=value
     * Column names can include:
     * - Simple columns: 'status', 'name'
     * - Relations: 'branch.name', 'customer.email'
     * - OR conditions: 'col1|col2|relation.col3' (will search across multiple)
     * 
     * @param Builder $query
     * @param string|null $tableId Optional table ID for column mapping
     * @param array $customFilters Optional array of manual filters (key => value)
     */
    protected function applyColumnFilters(Builder $query, ?string $tableId = null, ?array $columnMap = null, array $customFilters = []): void
    {
        $request = request();
        $filters = $request->input('filter', []);
        
        if (!empty($customFilters)) {
            $filters = array_merge($filters, $customFilters);
        }
        
        if (!is_array($filters) || empty($filters)) {
            return;
        }
        
        $processedKeys = [];

        // If column map is provided, use it to map filter keys to actual columns
        if ($columnMap !== null && is_array($columnMap)) {
            $hasMatchedFilter = false;
            foreach ($filters as $filterKey => $value) {
                if (!is_string($value) || trim($value) === '') continue;
                
                $term = trim($value);
                
                // Normalize filter key: convert double underscore back to dot, and check both numeric index and column name
                $normalizedKey = str_replace('__', '.', $filterKey);
                
                // Check if filter key exists in column map (try both original key and normalized)
                $columnConfig = null;
                if (isset($columnMap[$filterKey])) {
                    $columnConfig = $columnMap[$filterKey];
                } elseif (isset($columnMap[$normalizedKey])) {
                    $columnConfig = $columnMap[$normalizedKey];
                } elseif (is_numeric($filterKey) && isset($columnMap[(int)$filterKey])) {
                    $columnConfig = $columnMap[(int)$filterKey];
                }
                
                if ($columnConfig) {
                    $hasMatchedFilter = true;
                    $processedKeys[] = $filterKey;
                    
                    // Handle boolean columns with smarter text detection
                    if (isset($columnConfig['boolean']) && $columnConfig['boolean']) {
                        $lowerTerm = strtolower($term);
                        
                        // Check for text representations
                        $boolValue = null;
                        if (str_starts_with($lowerTerm, 'act') || $lowerTerm === '1' || $lowerTerm === 'true' || $lowerTerm === 'yes') {
                            $boolValue = true;
                        } elseif (str_starts_with($lowerTerm, 'inact') || str_starts_with($lowerTerm, 'non') || $lowerTerm === '0' || $lowerTerm === 'false' || $lowerTerm === 'no') {
                            $boolValue = false;
                        }
                        
                        // Fallback to standard filter
                        if ($boolValue === null) {
                            $boolValue = filter_var($term, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        }

                        if ($boolValue !== null) {
                            if ($boolValue === false) {
                                // For false, also include NULL values if the column accepts them
                                $query->where(function($q) use ($columnConfig) {
                                    $q->where($columnConfig['column'], false)
                                      ->orWhereNull($columnConfig['column']);
                                });
                            } else {
                                $query->where($columnConfig['column'], $boolValue);
                            }
                        }
                        continue;
                    }
                    
                    // Handle date columns (format check)
                    if (isset($columnConfig['type']) && $columnConfig['type'] === 'date') {
                        $column = $columnConfig['column'];
                        // Use SQL date formatting with Full Month (%M) and Short Month (%b) to match both "November" and "Nov"
                        // %d %M %Y %d %b %Y -> 15 November 2024 15 Nov 2024
                        $query->whereRaw("DATE_FORMAT($column, '%d %M %Y %d %b %Y') LIKE ?", ["%{$term}%"]);
                        continue;
                    }

                    // Handle relation columns
                    if (isset($columnConfig['relation'])) {
                        try {
                            $relation = $columnConfig['relation'];
                            $column = $columnConfig['column'] ?? 'name';
                            $query->whereHas($relation, function ($q) use ($column, $term) {
                                $q->where($column, 'LIKE', "%{$term}%");
                            });
                        } catch (\Exception $e) {
                            // Log error but continue processing other filters
                            \Log::warning('Error applying relation filter in ColumnFilterTrait', [
                                'relation' => $columnConfig['relation'] ?? null,
                                'error' => $e->getMessage()
                            ]);
                        }
                        continue;
                    }
                    
                    // Handle direct columns
                    if (isset($columnConfig['column'])) {
                        $query->where($columnConfig['column'], 'LIKE', "%{$term}%");
                        continue;
                    }
                }
            }
            
            // If no filters matched the column map, fall through to default behavior
            // if ($hasMatchedFilter) {
            //    return;
            // }
            // Actually, we should probably allow mixing mapped and unmapped filters.
            // But the original logic returned early. Let's comment out the return to allow fall-through for unmapped filters.
        }
        
        // Default behavior: auto-apply filters from request
        foreach ($filters as $columnSpec => $value) {
            if (!is_string($value) || trim($value) === '') continue;
            if (in_array($columnSpec, $processedKeys)) continue;
            
            $term = trim($value);
            // Normalize double underscore delimiter back to dot for relations
            $columnSpec = str_replace('__', '.', $columnSpec);
            
            // Check if this is an OR condition (pipe-separated)
            if (strpos($columnSpec, '|') !== false) {
                $columns = explode('|', $columnSpec);
                $query->where(function ($q) use ($columns, $term) {
                    foreach ($columns as $col) {
                        $this->applySingleFilter($q, trim($col), $term, true);
                    }
                });
            } else {
                $this->applySingleFilter($query, $columnSpec, $term, false);
            }
        }
    }
    
    /**
     * Apply a single filter condition to the query.
     * Supports:
     * - Direct columns: 'name', 'status'
     * - Nested relations: 'customer.name', 'quotation.prospect.company_name'
     * 
     * @param Builder $query
     * @param string $columnSpec e.g., 'name' or 'customer.email'
     * @param string $term search term
     * @param bool $useOr whether to use orWhere/orWhereHas
     */
    private function applySingleFilter(Builder $query, string $columnSpec, string $term, bool $useOr = false): void
    {
        // Check if this is a relation (contains dot)
        if (strpos($columnSpec, '.') !== false) {
            $parts = explode('.', $columnSpec);
            
            // Check if the first part is the table name
            if ($parts[0] === $query->getModel()->getTable()) {
                // It's a qualified column, treat as direct column
                if ($useOr) {
                    $query->orWhere($columnSpec, 'LIKE', "%{$term}%");
                } else {
                    $query->where($columnSpec, 'LIKE', "%{$term}%");
                }
                return;
            }

            $column = array_pop($parts); // Last part is the column
            $relations = $parts; // Everything before is relation path
            
            $this->applyNestedWhereHas($query, $relations, $column, $term, $useOr);
        } else {
            // Direct column
            if ($useOr) {
                $query->orWhere($columnSpec, 'LIKE', "%{$term}%");
            } else {
                $query->where($columnSpec, 'LIKE', "%{$term}%");
            }
        }
    }
    
    /**
     * Apply nested whereHas for dotted relation paths.
     * @param Builder $query
     * @param array $relations e.g. ['quotation','prospect']
     * @param string $column column name on the last relation
     * @param string $term search term
     * @param bool $useOr whether to use orWhereHas
     */
    private function applyNestedWhereHas(Builder $query, array $relations, string $column, string $term, bool $useOr = false): void
    {
        if (empty($relations)) {
            return;
        }

        $first = array_shift($relations);
        if (empty($relations)) {
            // Base case: single relation
            if ($useOr) {
                $query->orWhereHas($first, function (Builder $q) use ($column, $term) {
                    $q->where($column, 'LIKE', "%{$term}%");
                });
            } else {
                $query->whereHas($first, function (Builder $q) use ($column, $term) {
                    $q->where($column, 'LIKE', "%{$term}%");
                });
            }
            return;
        }

        // Recursive case: chain nested whereHas
        if ($useOr) {
            $query->orWhereHas($first, function (Builder $q) use ($relations, $column, $term) {
                $this->applyNestedWhereHas($q, $relations, $column, $term, true);
            });
        } else {
            $query->whereHas($first, function (Builder $q) use ($relations, $column, $term) {
                $this->applyNestedWhereHas($q, $relations, $column, $term);
            });
        }
    }
}
