<?php

namespace App\Repositories\Other;

use App\Models\CustomerPortal;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class CustomerPortalRepository extends BaseRepository
{
    public function __construct(CustomerPortal $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all portals with filters and pagination
     */
    public function getAllWithFilters($perPage = 15, $filters = [])
    {
        $query = $this->model->newQuery();

        // Apply filters
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status']);
        }

        if (isset($filters['created_from']) && !empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to']) && !empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        // Order by
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get portal by ID with relationships
     */
    public function findWithRelations($id, $relations = [])
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    /**
     * Get active portals only
     */
    public function getActivePortals()
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Check if portal exists with given conditions
     */
    public function exists(array $conditions, $excludeId = null): bool
    {
        $query = $this->model->newQuery();
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Get portal statistics
     */
    public function getStatistics()
    {
        return [
            'total' => $this->model->count(),
            'active' => $this->model->where('is_active', true)->count(),
            'inactive' => $this->model->where('is_active', false)->count(),
            'this_month' => $this->model->whereMonth('created_at', now()->month)->count(),
            'this_year' => $this->model->whereYear('created_at', now()->year)->count(),
        ];
    }

    /**
     * Bulk delete portals
     */
    public function bulkDelete($ids)
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * Get portals for dropdown
     */
    public function getForDropdown()
    {
        return $this->model->where('is_active', true)
                          ->select('id', 'name')
                          ->orderBy('name')
                          ->get();
    }
}
