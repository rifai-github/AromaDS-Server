<?php

namespace App\Repositories\Reports;

use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\DashboardPermission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DashboardRepository
{
    /**
     * Get all dashboards with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Dashboard::with(['widgets', 'permissions', 'creator', 'updater']);

        // Apply filters
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }

        if (isset($filters['is_default'])) {
            $query->where('is_default', $filters['is_default']);
        }

        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        return $query->orderBy('is_default', 'desc')
                    ->orderBy('name')
                    ->paginate($perPage);
    }

    /**
     * Get dashboard by ID
     */
    public function getById(int $id): ?Dashboard
    {
        return Dashboard::with(['widgets', 'permissions', 'creator', 'updater'])
                       ->find($id);
    }

    /**
     * Get user's accessible dashboards
     */
    public function getUserDashboards(int $userId, array $filters = []): Collection
    {
        $query = Dashboard::with(['widgets' => function ($q) {
            $q->where('is_active', true);
        }])
        ->where(function ($q) use ($userId) {
            $q->where('is_public', true)
              ->orWhere('created_by', $userId)
              ->orWhereHas('permissions', function ($permissionQuery) use ($userId) {
                  $permissionQuery->where('user_id', $userId);
              });
        });

        // Apply filters
        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }

        return $query->orderBy('is_default', 'desc')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Get default dashboard
     */
    public function getDefaultDashboard(): ?Dashboard
    {
        return Dashboard::with(['widgets' => function ($q) {
            $q->where('is_active', true);
        }])
        ->where('is_default', true)
        ->first();
    }

    /**
     * Create dashboard
     */
    public function create(array $data): Dashboard
    {
        return Dashboard::create($data);
    }

    /**
     * Update dashboard
     */
    public function update(Dashboard $dashboard, array $data): bool
    {
        return $dashboard->update($data);
    }

    /**
     * Delete dashboard
     */
    public function delete(Dashboard $dashboard): bool
    {
        return $dashboard->delete();
    }

    /**
     * Get dashboard widgets
     */
    public function getWidgets(int $dashboardId): Collection
    {
        return DashboardWidget::where('dashboard_id', $dashboardId)
                             ->where('is_active', true)
                             ->orderBy('position_y')
                             ->orderBy('position_x')
                             ->get();
    }

    /**
     * Add widget to dashboard
     */
    public function addWidget(int $dashboardId, array $data): DashboardWidget
    {
        $data['dashboard_id'] = $dashboardId;
        return DashboardWidget::create($data);
    }

    /**
     * Update widget
     */
    public function updateWidget(DashboardWidget $widget, array $data): bool
    {
        return $widget->update($data);
    }

    /**
     * Delete widget
     */
    public function deleteWidget(DashboardWidget $widget): bool
    {
        return $widget->delete();
    }

    /**
     * Get dashboard permissions
     */
    public function getPermissions(int $dashboardId): Collection
    {
        return DashboardPermission::where('dashboard_id', $dashboardId)
                                 ->with(['user', 'role'])
                                 ->get();
    }

    /**
     * Set dashboard permissions
     */
    public function setPermissions(int $dashboardId, array $permissions): void
    {
        // Remove existing permissions
        DashboardPermission::where('dashboard_id', $dashboardId)->delete();

        // Add new permissions
        foreach ($permissions as $permission) {
            $permission['dashboard_id'] = $dashboardId;
            DashboardPermission::create($permission);
        }
    }

    /**
     * Check if user has permission to dashboard
     */
    public function hasPermission(int $dashboardId, int $userId, string $permissionType = 'view'): bool
    {
        $dashboard = $this->getById($dashboardId);
        
        if (!$dashboard) {
            return false;
        }

        // Creator has all permissions
        if ($dashboard->created_by === $userId) {
            return true;
        }

        // Public dashboards can be viewed by everyone
        if ($dashboard->is_public && $permissionType === 'view') {
            return true;
        }

        // Check specific permissions
        return DashboardPermission::where('dashboard_id', $dashboardId)
                                 ->where('user_id', $userId)
                                 ->where('permission_type', $permissionType)
                                 ->exists();
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
     * Get dashboard statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_dashboards' => Dashboard::count(),
            'public_dashboards' => Dashboard::where('is_public', true)->count(),
            'private_dashboards' => Dashboard::where('is_public', false)->count(),
            'default_dashboards' => Dashboard::where('is_default', true)->count(),
            'total_widgets' => DashboardWidget::count(),
            'active_widgets' => DashboardWidget::where('is_active', true)->count(),
        ];
    }

    /**
     * Search dashboards
     */
    public function search(string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Dashboard::with(['widgets', 'permissions'])
                       ->where(function ($q) use ($search) {
                           $q->where('name', 'like', "%{$search}%")
                             ->orWhere('description', 'like', "%{$search}%");
                       })
                       ->orderBy('name')
                       ->paginate($perPage);
    }

    /**
     * Get dashboards by category
     */
    public function getByCategory(string $category, int $perPage = 15): LengthAwarePaginator
    {
        return Dashboard::with(['widgets', 'permissions'])
                       ->where('category', $category)
                       ->orderBy('name')
                       ->paginate($perPage);
    }

    /**
     * Get recent dashboards
     */
    public function getRecent(int $userId, int $limit = 5): Collection
    {
        return Dashboard::with(['widgets'])
                       ->where(function ($q) use ($userId) {
                           $q->where('is_public', true)
                             ->orWhere('created_by', $userId)
                             ->orWhereHas('permissions', function ($permissionQuery) use ($userId) {
                                 $permissionQuery->where('user_id', $userId);
                             });
                       })
                       ->orderBy('updated_at', 'desc')
                       ->limit($limit)
                       ->get();
    }

    /**
     * Duplicate dashboard
     */
    public function duplicate(Dashboard $dashboard, string $newName): Dashboard
    {
        $newDashboard = Dashboard::create([
            'name' => $newName,
            'description' => $dashboard->description,
            'layout_config' => $dashboard->layout_config,
            'is_public' => false,
            'is_default' => false,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
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
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]);
        }

        return $newDashboard;
    }
}
