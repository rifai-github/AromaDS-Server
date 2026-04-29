<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BenchmarkPermissionAccess extends Command
{
    protected $signature = 'perf:permissions-benchmark {--user= : User ID to benchmark} {--iterations=10 : Number of simulated requests}';

    protected $description = 'Benchmark repeated role/permission/menu checks for a typical web request';

    public function handle(): int
    {
        $iterations = max(1, (int) $this->option('iterations'));
        $user = $this->resolveUser();

        if (!$user) {
            $this->error('No suitable user found for benchmark.');
            return self::FAILURE;
        }

        $modules = ['marketing', 'operational', 'finance', 'warehouse', 'company', 'system'];
        $menuItems = [
            'marketing.dashboard',
            'marketing.commissions.dashboard',
            'marketing.pipeline',
            'marketing.surveys',
            'marketing.quotations',
            'marketing.contracts',
            'marketing.contract-terminations',
            'marketing.contract-assigned',
            'marketing.contract-switchings',
            'marketing.aroma-changes',
            'marketing.job-advices',
            'marketing.lost-unit-reports',
            'marketing.customers',
            'marketing.master-corporates',
            'marketing.customer-contacts',
            'marketing.customer-taxes',
            'marketing.customer-types',
            'company.company-virtual-accounts',
            'system.salutations',
            'operational.master-buildings',
            'operational.job-schedules',
            'operational.job-assign-material-issues',
            'operational.master-team',
            'operational.master-rooms',
            'finance.invoices',
            'finance.invoice-follow-ups',
            'finance.tax-file-imports',
            'finance.tax-file-exports',
            'finance.tax-settings',
            'finance.commission-levels',
            'finance.marketing-levels',
            'finance.cr-variables',
            'finance.marketing-targets',
            'warehouse.master-products',
            'warehouse.product-types',
            'warehouse.brand-variants',
            'warehouse.master-rentals',
            'warehouse.inventory-issuings',
            'warehouse.inventory-receivings',
            'warehouse.inventory-requests',
            'warehouse.stock-opnames',
            'warehouse.stock-adjustments',
            'warehouse.serial-numbers',
            'warehouse.unit-on-walls',
            'company.branches',
            'company.master-banks',
            'company.bank-payments',
            'company.master-price-slabs',
            'company.companies',
            'company.positions',
            'company.master-options',
            'system.departments',
            'system.users',
            'system.roles',
            'system.access-control',
            'system.provinces',
            'system.audit-trails',
        ];

        DB::flushQueryLog();
        DB::enableQueryLog();

        $startedAt = hrtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $benchUser = User::query()->findOrFail($user->id);

            $benchUser->getRoleName();
            $benchUser->hasRoleStartingWith('Marketing');
            $benchUser->hasRole('Admin');

            foreach ($modules as $module) {
                $benchUser->canAccessModule($module);
            }

            foreach ($menuItems as $menuItem) {
                $benchUser->canAccessMenuItem($menuItem);
            }
        }

        $elapsedMs = round((hrtime(true) - $startedAt) / 1_000_000, 2);
        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);
        $queryTimeMs = round(array_sum(array_column($queryLog, 'time')), 2);

        $this->line(json_encode([
            'user_id' => $user->id,
            'role' => $user->getRoleName(),
            'iterations' => $iterations,
            'checks_per_iteration' => 3 + count($modules) + count($menuItems),
            'elapsed_ms_total' => $elapsedMs,
            'elapsed_ms_avg_per_iteration' => round($elapsedMs / $iterations, 2),
            'db_query_count_total' => $queryCount,
            'db_query_count_avg_per_iteration' => round($queryCount / $iterations, 2),
            'db_query_time_ms_total' => $queryTimeMs,
            'db_query_time_ms_avg_per_iteration' => round($queryTimeMs / $iterations, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ], JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $userId = $this->option('user');

        if ($userId) {
            return User::query()->find($userId);
        }

        return User::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereHas('roles')
                    ->orWhereNotNull('roles');
            })
            ->orderBy('id')
            ->first();
    }
}
