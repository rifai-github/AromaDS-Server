<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Find record by ID
     */
    public function find($id)
    {
        return $this->model->find($id);
    }

    /**
     * Find record by ID or fail
     */
    public function findOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create new record
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Update record
     */
    public function update($id, array $data)
    {
        $record = $this->findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    /**
     * Delete record (soft delete if model uses SoftDeletes)
     */
    public function delete($id)
    {
        $record = $this->findOrFail($id);
        return $record->delete();
    }

    /**
     * Count records
     */
    public function count(array $conditions = []): int
    {
        $query = $this->model->newQuery();
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->count();
    }

    /**
     * Check if record exists
     */
    public function exists(array $conditions): bool
    {
        $query = $this->model->newQuery();
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->exists();
    }

    /**
     * Get paginated results
     */
    public function paginate($perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }
}