<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AuditSelectedColumns extends Command
{
    protected $signature = 'dev:audit-select-columns
        {--files=* : Specific PHP files to audit}
        {--strict : Return non-zero exit code when suspicious columns are found}';

    protected $description = 'Best-effort audit for select()/get([...]) and eager-load shorthand columns against schema';

    protected array $classToTableMap = [
        'AuditLog' => 'audit_logs',
        'Bank' => 'banks',
        'Branch' => 'branches',
        'Building' => 'buildings',
        'City' => 'cities',
        'Customer' => 'customers',
        'Department' => 'departments',
        'District' => 'districts',
        'JobAssignMaterialIssue' => 'job_assign_material_issues',
        'JobAssignSchedule' => 'job_assign_schedules',
        'LoginHistory' => 'login_histories',
        'MasterProduct' => 'master_products',
        'Permission' => 'permissions',
        'Role' => 'roles',
        'Subdistrict' => 'subdistricts',
        'Team' => 'teams',
        'User' => 'users',
        'Warehouse' => 'warehouses',
    ];

    protected array $relationToTableMap = [
        'accessLevels' => 'user_access_levels',
        'branch' => 'branches',
        'branches' => 'branches',
        'building' => 'buildings',
        'cities' => 'cities',
        'city' => 'cities',
        'createdBy' => 'users',
        'customer' => 'customers',
        'department' => 'departments',
        'districts' => 'districts',
        'items' => 'material_issue_items',
        'loginRestrictions' => 'user_login_restrictions',
        'packagingSize' => 'packaging_sizes',
        'permissions' => 'permissions',
        'previousProduct' => 'master_products',
        'product' => 'master_products',
        'productType' => 'product_types',
        'rolePermissions' => 'role_permissions',
        'roles' => 'roles',
        'subdistricts' => 'subdistricts',
        'team' => 'teams',
        'updatedBy' => 'users',
        'user' => 'users',
        'warehouse' => 'warehouses',
    ];

    protected array $tableColumns = [];

    public function handle(): int
    {
        $files = $this->resolveFiles();

        if (empty($files)) {
            $this->warn('No files to audit.');
            return self::SUCCESS;
        }

        $issues = [];

        foreach ($files as $file) {
            $issues = array_merge($issues, $this->auditFile($file));
        }

        if (empty($issues)) {
            $this->info('No suspicious selected columns found in audited files.');
            return self::SUCCESS;
        }

        $this->warn('Suspicious selected columns found:');
        foreach ($issues as $issue) {
            $this->line(sprintf(
                '- %s | table=%s | columns=%s | snippet=%s',
                $issue['file'],
                $issue['table'],
                implode(', ', $issue['columns']),
                $issue['snippet']
            ));
        }

        return $this->option('strict') ? self::FAILURE : self::SUCCESS;
    }

    protected function resolveFiles(): array
    {
        $files = $this->option('files');

        if (!empty($files)) {
            return array_values(array_filter($files, fn ($file) => is_file(base_path($file))));
        }

        return [
            'app/Http/Controllers/Operational/JobAssignMaterialIssueController.php',
            'app/Http/Controllers/AccessControlController.php',
            'app/Http/Controllers/System/UserController.php',
            'app/Http/Controllers/System/RoleController.php',
            'app/Http/Controllers/System/AuditLogController.php',
            'app/Http/Controllers/AuditTrailController.php',
            'app/Http/Controllers/System/ProvinceController.php',
            'app/Http/Controllers/Company/CustomerController.php',
            'app/Http/Controllers/Operational/JobAssignScheduleController.php',
            'app/Services/DataHierarchyService.php',
        ];
    }

    protected function auditFile(string $relativePath): array
    {
        $content = file_get_contents(base_path($relativePath));
        $issues = [];

        foreach ($this->findModelSelects($content) as $match) {
            $table = $this->classToTableMap[$match['model']] ?? null;
            if (!$table) {
                continue;
            }

            $columns = $this->normalizeColumns($match['columns']);
            $unknown = $this->findUnknownColumns($table, $columns);

            if ($unknown) {
                $issues[] = [
                    'file' => $relativePath,
                    'table' => $table,
                    'columns' => $unknown,
                    'snippet' => $this->snippet($match['full']),
                ];
            }
        }

        foreach ($this->findEagerLoadShorthand($content) as $match) {
            $table = $this->relationToTableMap[$match['relation']] ?? null;
            if (!$table) {
                continue;
            }

            $columns = $this->normalizeColumns($match['columns']);
            $unknown = $this->findUnknownColumns($table, $columns);

            if ($unknown) {
                $issues[] = [
                    'file' => $relativePath,
                    'table' => $table,
                    'columns' => $unknown,
                    'snippet' => $this->snippet($match['full']),
                ];
            }
        }

        return $issues;
    }

    protected function findModelSelects(string $content): array
    {
        $results = [];

        $patterns = [
            '/(?P<full>(?P<model>[A-Z][A-Za-z0-9_]+)::query\(\)\s*->select\((?P<columns>.*?)\))/s',
            '/(?P<full>(?P<model>[A-Z][A-Za-z0-9_]+)::select\((?P<columns>.*?)\))/s',
            '/(?P<full>(?P<model>[A-Z][A-Za-z0-9_]+)::where[a-zA-Z0-9_]*\([^)]*\)\s*->select\((?P<columns>.*?)\))/s',
            '/(?P<full>(?P<model>[A-Z][A-Za-z0-9_]+)::query\(\)\s*->with\([^)]*\)\s*->select\((?P<columns>.*?)\))/s',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $results[] = $match;
                }
            }
        }

        return $results;
    }

    protected function findEagerLoadShorthand(string $content): array
    {
        $results = [];

        if (preg_match_all('/(?P<full>[\'"](?:[A-Za-z0-9_]+\.)*(?P<relation>[A-Za-z0-9_]+):(?P<columns>[^\'"]+)[\'"])/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $results[] = $match;
            }
        }

        return $results;
    }

    protected function normalizeColumns(string $rawColumns): array
    {
        preg_match_all('/[\'"]([^\'"]+)[\'"]/', $rawColumns, $matches);
        $columns = [];

        foreach ($matches[1] as $column) {
            $column = trim($column);

            if ($column === '*' || str_contains($column, ' as ') || str_contains($column, '(') || str_contains($column, '->')) {
                continue;
            }

            if (str_contains($column, '.')) {
                continue;
            }

            if ($column === '' || is_numeric($column)) {
                continue;
            }

            $columns[] = $column;
        }

        return array_values(array_unique($columns));
    }

    protected function findUnknownColumns(string $table, array $columns): array
    {
        if (empty($columns) || !Schema::hasTable($table)) {
            return [];
        }

        if (!isset($this->tableColumns[$table])) {
            $this->tableColumns[$table] = Schema::getColumnListing($table);
        }

        $knownColumns = $this->tableColumns[$table];

        return array_values(array_filter($columns, function ($column) use ($knownColumns) {
            return !in_array($column, $knownColumns, true);
        }));
    }

    protected function snippet(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value));
        return mb_strlen($value) > 140 ? mb_substr($value, 0, 137) . '...' : $value;
    }
}
