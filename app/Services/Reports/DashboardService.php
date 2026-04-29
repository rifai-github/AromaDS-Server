<?php

namespace App\Services\Reports;

use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\DashboardPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    /**
     * Create a new dashboard
     */
    public function createDashboard(array $data): Dashboard
    {
        return DB::transaction(function () use ($data) {
            $dashboard = Dashboard::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'layout_config' => $data['layout_config'] ?? '[]',
                'is_public' => $data['is_public'] ?? false,
                'is_default' => $data['is_default'] ?? false,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Set as default if specified
            if ($data['is_default'] ?? false) {
                $this->setAsDefault($dashboard);
            }

            // Set permissions if provided
            if (isset($data['permissions'])) {
                $this->setPermissions($dashboard, $data['permissions']);
            }

            return $dashboard->load('widgets', 'permissions');
        });
    }

    /**
     * Update dashboard
     */
    public function updateDashboard(Dashboard $dashboard, array $data): Dashboard
    {
        return DB::transaction(function () use ($dashboard, $data) {
            $dashboard->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? $dashboard->description,
                'layout_config' => $data['layout_config'] ?? $dashboard->layout_config,
                'is_public' => $data['is_public'] ?? $dashboard->is_public,
                'is_default' => $data['is_default'] ?? $dashboard->is_default,
                'updated_by' => Auth::id()
            ]);

            // Set as default if specified
            if ($data['is_default'] ?? false) {
                $this->setAsDefault($dashboard);
            }

            // Update permissions if provided
            if (isset($data['permissions'])) {
                $this->setPermissions($dashboard, $data['permissions']);
            }

            return $dashboard->load('widgets', 'permissions');
        });
    }

    /**
     * Add widget to dashboard
     */
    public function addWidget(Dashboard $dashboard, array $data): DashboardWidget
    {
        return DB::transaction(function () use ($dashboard, $data) {
            $widget = DashboardWidget::create([
                'dashboard_id' => $dashboard->id,
                'widget_type' => $data['widget_type'],
                'title' => $data['title'],
                'config' => $data['config'] ?? '{}',
                'position_x' => $data['position_x'] ?? 0,
                'position_y' => $data['position_y'] ?? 0,
                'width' => $data['width'] ?? 4,
                'height' => $data['height'] ?? 3,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            return $widget;
        });
    }

    /**
     * Update widget
     */
    public function updateWidget(DashboardWidget $widget, array $data): DashboardWidget
    {
        $widget->update([
            'title' => $data['title'] ?? $widget->title,
            'config' => $data['config'] ?? $widget->config,
            'position_x' => $data['position_x'] ?? $widget->position_x,
            'position_y' => $data['position_y'] ?? $widget->position_y,
            'width' => $data['width'] ?? $widget->width,
            'height' => $data['height'] ?? $widget->height,
            'is_active' => $data['is_active'] ?? $widget->is_active,
            'updated_by' => Auth::id()
        ]);

        return $widget;
    }

    /**
     * Remove widget from dashboard
     */
    public function removeWidget(DashboardWidget $widget): bool
    {
        return $widget->delete();
    }

    /**
     * Set dashboard permissions
     */
    public function setPermissions(Dashboard $dashboard, array $permissions): void
    {
        // Remove existing permissions
        $dashboard->permissions()->delete();

        // Add new permissions
        foreach ($permissions as $permission) {
            DashboardPermission::create([
                'dashboard_id' => $dashboard->id,
                'user_id' => $permission['user_id'] ?? null,
                'role_id' => $permission['role_id'] ?? null,
                'permission_type' => $permission['permission_type'], // view, edit, admin
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);
        }
    }

    /**
     * Set dashboard as default
     */
    public function setAsDefault(Dashboard $dashboard): void
    {
        // Remove default flag from other dashboards
        Dashboard::where('is_default', true)->update(['is_default' => false]);

        // Set this dashboard as default
        $dashboard->update(['is_default' => true]);
    }

    /**
     * Get user's accessible dashboards
     */
    public function getUserDashboards(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return Dashboard::where(function ($query) use ($user) {
            $query->where('is_public', true)
                  ->orWhere('created_by', $user->id)
                  ->orWhereHas('permissions', function ($q) use ($user) {
                      $q->where('user_id', $user->id)
                        ->orWhereIn('role_id', $user->roles->pluck('id'));
                  });
        })
        ->with(['widgets' => function ($query) {
            $query->where('is_active', true);
        }])
        ->orderBy('is_default', 'desc')
        ->orderBy('name')
        ->get();
    }

    /**
     * Get dashboard data for display
     */
    public function getDashboardData(Dashboard $dashboard): array
    {
        $data = [
            'dashboard' => $dashboard,
            'widgets' => $dashboard->widgets()->where('is_active', true)->get(),
            'permissions' => $dashboard->permissions
        ];

        // Load widget data
        foreach ($data['widgets'] as $widget) {
            $data['widget_data'][$widget->id] = $this->getWidgetData($widget);
        }

        return $data;
    }

    /**
     * Get widget data based on type
     */
    public function getWidgetData(DashboardWidget $widget): array
    {
        $config = json_decode($widget->config, true) ?? [];

        switch ($widget->widget_type) {
            case 'chart':
                return $this->getChartData($config);
            case 'table':
                return $this->getTableData($config);
            case 'kpi':
                return $this->getKpiData($config);
            case 'list':
                return $this->getListData($config);
            default:
                return [];
        }
    }

    /**
     * Get chart data
     */
    private function getChartData(array $config): array
    {
        // Implement chart data logic based on config
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => [12, 19, 3, 5, 2, 3],
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    /**
     * Get table data
     */
    private function getTableData(array $config): array
    {
        // Implement table data logic based on config
        return [
            'headers' => ['Name', 'Value', 'Status'],
            'rows' => [
                ['Item 1', '100', 'Active'],
                ['Item 2', '200', 'Inactive'],
                ['Item 3', '300', 'Active']
            ]
        ];
    }

    /**
     * Get KPI data
     */
    private function getKpiData(array $config): array
    {
        // Implement KPI data logic based on config
        return [
            'value' => 1250,
            'label' => 'Total Sales',
            'change' => 12.5,
            'change_type' => 'increase'
        ];
    }

    /**
     * Get list data
     */
    private function getListData(array $config): array
    {
        // Implement list data logic based on config
        return [
            'items' => [
                ['title' => 'Item 1', 'description' => 'Description 1'],
                ['title' => 'Item 2', 'description' => 'Description 2'],
                ['title' => 'Item 3', 'description' => 'Description 3']
            ]
        ];
    }

    /**
     * Duplicate dashboard
     */
    public function duplicateDashboard(Dashboard $dashboard, string $newName): Dashboard
    {
        return DB::transaction(function () use ($dashboard, $newName) {
            $newDashboard = Dashboard::create([
                'name' => $newName,
                'description' => $dashboard->description,
                'layout_config' => $dashboard->layout_config,
                'is_public' => false,
                'is_default' => false,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Duplicate widgets
            foreach ($dashboard->widgets as $widget) {
                DashboardWidget::create([
                    'dashboard_id' => $newDashboard->id,
                    'widget_type' => $widget->widget_type,
                    'title' => $widget->title,
                    'config' => $widget->config,
                    'position_x' => $widget->position_x,
                    'position_y' => $widget->position_y,
                    'width' => $widget->width,
                    'height' => $widget->height,
                    'is_active' => $widget->is_active,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            return $newDashboard->load('widgets');
        });
    }
}
