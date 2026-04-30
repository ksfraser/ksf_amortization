<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * Phase 1: Base Repository
 * Provides common database operations and data access patterns
 */
abstract class BaseRepository
{
    protected Model $model;

    abstract public function getModel();

    public function __construct()
    {
        $this->model = $this->getModel();
    }

    /**
     * Get all records
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * Get record by ID
     */
    public function findById(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }

    /**
     * Find or fail
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->findOrFail($id, $columns);
    }

    /**
     * Create new record
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update record
     */
    public function update(int $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);
        return $model;
    }

    /**
     * Delete record
     */
    public function delete(int $id): bool
    {
        return $this->findOrFail($id)->delete();
    }

    /**
     * Get paginated results
     */
    public function paginate(int $perPage = 15, array $columns = ['*'])
    {
        return $this->model->paginate($perPage, $columns);
    }

    /**
     * Search records by multiple fields
     */
    public function search(string $query, array $searchFields)
    {
        $searchQuery = $this->model;

        foreach ($searchFields as $field) {
            $searchQuery = $searchQuery->orWhere($field, 'like', "%{$query}%");
        }

        return $searchQuery->get();
    }

    /**
     * Filter records
     */
    public function filter(array $filters)
    {
        $query = $this->model;

        foreach ($filters as $field => $value) {
            if ($value !== null) {
                $query = $query->where($field, $value);
            }
        }

        return $query->get();
    }

    /**
     * Get count
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Check if record exists
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Get first record matching condition
     */
    public function firstWhere(array $condition): ?Model
    {
        return $this->model->where($condition)->first();
    }

    /**
     * Get all where
     */
    public function allWhere(array $conditions): Collection
    {
        $query = $this->model;

        foreach ($conditions as $field => $value) {
            $query = $query->where($field, $value);
        }

        return $query->get();
    }
}
