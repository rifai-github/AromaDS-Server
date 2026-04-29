<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

/**
 * AutoFilterable Trait
 * 
 * Automatically applies column filters from request to any Eloquent model.
 * 
 * Usage in Model:
 * 
 * use AutoFilterable;
 * 
 * protected $filterableRelations = [
 *     'customer' => 'name',      // Simple relation
 *     'branch' => 'branch_name', // Relation with custom column
 *     'quotation.prospect' => 'company_name', // Nested relation
 * ];
 * 
 * That's it! Filters will auto-apply when query is executed.
 */
trait AutoFilterable
{
    /**
     * Local Scope: Apply column filters manually.
     * Usage: $query->filter($request->all());
     * 
     * @param Builder $query
     * @param array $filters Input array, usually $request->all() or specific 'filter' array
     */
    public function scopeFilter(Builder $query, $filters = [])
    {
        // If filters are passed within a wrapper (standard Request::all() structure), extract them
        if (isset($filters['filter']) && is_array($filters['filter'])) {
            $rawFilters = $filters['filter'];
        } else {
            // Otherwise assume the array passed IS the filters array
            $rawFilters = $filters;
        }
        
        $request = request();
        $skipFilters = $request->input('_skip_auto_filter', []);
        
        // --- Global Sorting Logic (keep reading from request for now as it's separate) ---
        $sortColumn = $request->input('sort');
        $sortDirection = $request->input('direction', 'asc');
        
        if ($sortColumn && in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $model = $query->getModel();
            $table = $model->getTable();
            
            // Handle Relation Sorting
            if (strpos($sortColumn, '.') !== false) {
                $parts = explode('.', $sortColumn);
                $filterRelation = $parts[0];
                $filterColumn = end($parts);
                
                // Generic relation mapping handling
                $relationMap = [
                    'customer' => ['table' => 'customers', 'fk' => 'customer_id'],
                    'prospect' => ['table' => 'prospects', 'fk' => 'prospect_id'],
                    'quotation' => ['table' => 'quotations', 'fk' => 'quotation_id'],
                    'branch' => ['table' => 'branches', 'fk' => 'branch_id'],
                    'survey' => ['table' => 'surveys', 'fk' => 'survey_id'], 
                ];

                // User relations mapping
                $userRelations = [
                    'marketing' => 'marketing_id',
                    'creator' => 'created_by',
                    'updater' => 'updated_by',
                    'approver' => 'approved_by',
                ];

                if (isset($relationMap[$filterRelation])) {
                     $map = $relationMap[$filterRelation];
                     $relatedTable = $map['table'];
                     $foreignKey = $map['fk'];
                     
                     if (\Schema::hasColumn($table, $foreignKey)) {
                         // Use Subquery for sorting to avoid JOIN/SELECT issues
                         // This is safer for data integrity in views
                         $query->orderByRaw("(SELECT $filterColumn FROM $relatedTable WHERE $relatedTable.id = $table.$foreignKey) $sortDirection");
                     }
                } elseif (isset($userRelations[$filterRelation])) {
                     $fk = $userRelations[$filterRelation];
                     $relatedTable = 'users';
                     
                     if (\Schema::hasColumn($table, $fk)) {
                         $query->orderByRaw("(SELECT $filterColumn FROM $relatedTable WHERE $relatedTable.id = $table.$fk) $sortDirection");
                     }
                } else {
                    // Fallback for unmapped or standard column sorting if needed
                    // But usually dot notation implies relation. 
                    // If we can't map it, ignoring it is safer than crashing.
                }
            } else {
                 // Direct column sorting - only if column exists on this table
                 if (\Schema::hasColumn($table, $sortColumn)) {
                     $query->orderBy($sortColumn, $sortDirection);
                 }
            }
        }
        // ----------------------------------
        
        if (!is_array($rawFilters) || empty($rawFilters)) {
            return;
        }
        
        foreach ($rawFilters as $columnSpec => $value) {
            if (!is_string($value) || trim($value) === '') {
                continue;
            }
            
            // Check if this filter should be skipped (handled manually in controller)
            if (isset($skipFilters[$columnSpec]) && $skipFilters[$columnSpec]) {
                continue;
            }
            
            // Convert double underscores back to dots (relation separator)
            // JavaScript sends branch__name to avoid PHP converting dots to underscores
            $originalSpec = $columnSpec;
            $columnSpec = str_replace('__', '.', $columnSpec);
            
            // Also check normalized key in skip list
            if (isset($skipFilters[$columnSpec]) && $skipFilters[$columnSpec]) {
                continue;
            }
            
            $term = trim($value);
            
            // Check if this is an OR condition (pipe-separated)
            if (strpos($columnSpec, '|') !== false) {
                $columns = explode('|', $columnSpec);
                $query->where(function ($q) use ($columns, $term) {
                    foreach ($columns as $col) {
                        $this->applySingleColumnFilter($q, trim($col), $term, true);
                    }
                });
            } else {
                $this->applySingleColumnFilter($query, $columnSpec, $term, false);
            }
        }
    }
    
    /**
     * Apply filter to a single column
     */
    protected function applySingleColumnFilter(Builder $query, string $columnSpec, string $term, bool $useOr = false)
    {
        $model = $query->getModel();
        $modelTable = $model->getTable();
        
        // Check if this is a relation (contains dot)
        if (strpos($columnSpec, '.') !== false) {
            // Validate that the relation exists on the current model
            $parts = explode('.', $columnSpec);
            $relationName = $parts[0];
            
            // IMPORTANT: If the first part of the filter matches a table name (plural form),
            // and it doesn't match the current model's table, skip this filter.
            // This prevents filters like "surveys.updated_at" from being applied to "prospects" model
            // Check if relationName is a table name (common Laravel convention: table names are plural)
            $commonTableNames = ['surveys', 'prospects', 'quotations', 'contracts', 'customers', 'buildings', 'users', 'roles'];
            if (in_array($relationName, $commonTableNames) && $relationName !== $modelTable) {
                // This filter is specifically for a different table, skip it
                return;
            }
            
            // Check if the filter is for a different table/model
            // If columnSpec starts with a table name that doesn't match current model's table, skip it
            if ($relationName !== $modelTable && !method_exists($model, $relationName)) {
                // This filter is for a different model/table, skip it
                return;
            }
            
            // Check if the relation method exists on this model
            if (!method_exists($model, $relationName)) {
                // Skip this filter - relation doesn't exist on this model
                return;
            }
            
            try {
                $this->applyRelationFilter($query, $columnSpec, $term, $useOr);
            } catch (\Exception $e) {
                // Log and skip this filter if it causes an error
                \Log::error("AutoFilterable: Error applying relation filter for {$columnSpec}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                return;
            }
        } else {
            // Direct column filter - check if it's for this model's table
            // If columnSpec starts with a table name (singular or plural), check if it matches current model
            if (strpos($columnSpec, '_') !== false) {
                // Better approach: check if columnSpec starts with table name followed by underscore
                // This avoids skipping quotation_number when table is quotations
                if (strpos($columnSpec, $modelTable . '_') === 0 || strpos($columnSpec, Str::singular($modelTable) . '_') === 0) {
                    // It belongs to this model, do nothing (continue to apply filter)
                } elseif (!$this->columnExistsOnModel($model, $columnSpec)) {
                    // This filter is definitely for a different table and doesn't exist on this model
                    return;
                }
            }

            // Apply direct column filter
            $whereMethod = $useOr ? 'orWhere' : 'where';
            
            if (Schema::hasColumn($modelTable, $columnSpec)) {
                $columnType = Schema::getColumnType($modelTable, $columnSpec) ?? 'string';

                if (in_array($columnType, ['date', 'datetime', 'timestamp'])) {
                    $this->applyDateLike($query, $columnSpec, $term, $useOr);
                } elseif (in_array($columnType, ['integer', 'bigint', 'decimal', 'float'])) {
                    $this->applyNumericLike($query, $columnSpec, $term, $useOr);
                } elseif (in_array($columnType, ['boolean', 'tinyint'])) {
                    $this->applyBooleanLike($query, $columnSpec, $term, $useOr);
                } else {
                    $query->$whereMethod($columnSpec, 'LIKE', "%{$term}%");
                }
            } else {
                // Fallback: try LIKE match anyway (standard where) if it's not a known column but might be an alias or something
                // But safer to just skip it if it triggers crashes on getColumnType
            }
        }
    }
    
    /**
     * Check if a column exists on the model
     */
    protected function columnExistsOnModel($model, string $column): bool
    {
        // Check if in fillable
        if (in_array($column, $model->getFillable())) {
            return true;
        }
        
        // Check if in dates (for timestamps)
        if (in_array($column, ['created_at', 'updated_at', 'deleted_at'])) {
            return true;
        }
        
        // For other columns, we'll allow them (to avoid false negatives)
        // The database will throw error if column doesn't exist anyway
        return false;
    }
    
    /**
     * Apply filter to a relation column
     */
    protected function applyRelationFilter(Builder $query, string $columnSpec, string $term, bool $useOr = false)
    {
        $parts = explode('.', $columnSpec);
        $column = array_pop($parts); // Last part is the column name
        $relationPath = implode('.', $parts); // Everything else is the relation path
        
        // Handle nested relations recursively
        $this->applyNestedRelationFilter($query, explode('.', $relationPath), $column, $term, $useOr);
    }
    
    /**
     * Apply nested relation filters recursively
     */
    protected function applyNestedRelationFilter(Builder $query, array $relations, string $column, string $term, bool $useOr = false)
    {
        if (empty($relations)) {
            return;
        }
        
        $firstRelation = array_shift($relations);
        
        if (empty($relations)) {
            // Base case: last relation in chain
            if ($useOr) {
                $query->orWhereHas($firstRelation, function (Builder $q) use ($column, $term) {
                    // Mark the query to skip auto-filtering to prevent infinite loops
                    $q->getQuery()->skipAutoFilter = true;
                    $q->where($column, 'LIKE', "%{$term}%");
                });
            } else {
                $query->whereHas($firstRelation, function (Builder $q) use ($column, $term) {
                    // Mark the query to skip auto-filtering to prevent infinite loops
                    $q->getQuery()->skipAutoFilter = true;
                    $q->where($column, 'LIKE', "%{$term}%");
                });
            }
        } else {
            // Recursive case: still have nested relations
            if ($useOr) {
                $query->orWhereHas($firstRelation, function (Builder $q) use ($relations, $column, $term) {
                    // Mark the query to skip auto-filtering to prevent infinite loops
                    $q->getQuery()->skipAutoFilter = true;
                    $this->applyNestedRelationFilter($q, $relations, $column, $term, true);
                });
            } else {
                $query->whereHas($firstRelation, function (Builder $q) use ($relations, $column, $term) {
                    // Mark the query to skip auto-filtering to prevent infinite loops
                    $q->getQuery()->skipAutoFilter = true;
                    $this->applyNestedRelationFilter($q, $relations, $column, $term);
                });
            }
        }
    }
    
    /**
     * Apply flexible LIKE matching for date/datetime columns using common formats.
     */
    private function applyDateLike(Builder $query, string $column, string $term, bool $useOr = false): void
    {
        $formats = [
            '%Y-%m-%d',      // 2025-10-13
            '%d-%m-%Y',      // 13-10-2025
            '%d/%m/%Y',      // 13/10/2025
            '%d %M %Y',      // 13 October 2025
            '%d %b %Y',      // 13 Oct 2025
            '%Y-%m',         // 2025-10
            '%m/%Y',         // 10/2025
            '%Y'             // 2025
        ];

        $whereMethod = $useOr ? 'orWhere' : 'where';
        $query->$whereMethod(function (Builder $q) use ($column, $term, $formats) {
            // Raw date string match as stored (useful if user inputs 2025-10-13 directly)
            $q->where($column, 'LIKE', "%{$term}%");
            foreach ($formats as $fmt) {
                $q->orWhereRaw("DATE_FORMAT({$column}, '{$fmt}') LIKE ?", ["%{$term}%"]);
            }
        });
    }

    /**
     * Apply LIKE matching against numeric columns, supporting formatted inputs.
     */
    private function applyNumericLike(Builder $query, string $column, string $term, bool $useOr = false): void
    {
        // Remove common number formatting (commas, dots for thousands separator, Rp, etc)
        $cleanTerm = preg_replace('/[^0-9\.-]/', '', (string)$term);
        
        // If user typed pure numbers or cleaned term is valid
        if ($cleanTerm !== '' && is_numeric($cleanTerm)) {
            // Use LIKE match on the numeric value cast as string
            // This allows partial matching: "110" matches "110000.00", "1100.00", "11000.00", etc.
            if ($useOr) {
                $query->orWhereRaw("CAST({$column} AS CHAR) LIKE ?", ["%{$cleanTerm}%"]);
            }
        } else {
            // Fallback to string LIKE if not numeric
            if ($useOr) {
                $query->orWhere($column, 'LIKE', "%{$term}%");
            } else {
                $query->where($column, 'LIKE', "%{$term}%");
            }
        }
    }

    /**
     * Apply loose matching for boolean columns (supporting "Active"/"Inactive", "Yes"/"No", etc).
     */
    private function applyBooleanLike(Builder $query, string $column, string $term, bool $useOr = false): void
    {
        $termLower = strtolower(trim($term));
        $value = null;

        // Map common string representations to boolean values
        // Case 1: Positive terms
        if (in_array($termLower, ['active', 'yes', 'true', '1', 'y', 'on', 'system', 'reserved'])) {
            $value = 1;
        } 
        // Case 2: Negative terms
        elseif (in_array($termLower, ['inactive', 'no', 'false', '0', 'n', 'off', 'user', 'public'])) {
            $value = 0;
        }

        $whereMethod = $useOr ? 'orWhere' : 'where';

        if ($value !== null) {
            // Exact match for resolved boolean
            $query->$whereMethod($column, $value);
        } else {
            // Fallback: If partial match is needed (e.g., "Act" for Active), we use WHERE field = ...
            // But typical boolean fields are 0/1. LIKE behavior on 0/1 is weird.
            // We'll try to guess based on partial strings
            if (str_contains('active', $termLower) || str_contains('system', $termLower)) {
                 $query->$whereMethod($column, 1);
            } elseif (str_contains('inactive', $termLower) || str_contains('user', $termLower)) {
                 $query->$whereMethod($column, 0);
            } else {
                 // No match, probably won't find anything, but safe fallback
                 $query->$whereMethod($column, 'LIKE', "%{$term}%");
            }
        }
    }
}

