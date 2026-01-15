<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        protected Product $model
    ) {}

    public function all(array $filters = [], string $sort = 'newest', int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $sort);

        return $query->paginate($perPage);
    }

    public function withCategory(array $filters = [], string $sort = 'newest', int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with('category');

        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $sort);

        return $query->paginate($perPage);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        // Поиск по подстроке в названии
        if (!empty($filters['q'])) {
            $searchTerm = $filters['q'];

            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        // Фильтр по цене
        if (!empty($filters['price_from'])) {
            $query->where('price', '>=', (float) $filters['price_from']);
        }

        if (!empty($filters['price_to'])) {
            $query->where('price', '<=', (float) $filters['price_to']);
        }

        // Фильтр по категории
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Фильтр по наличию
        if (isset($filters['in_stock'])) {
            $query->where('in_stock', filter_var($filters['in_stock'], FILTER_VALIDATE_BOOLEAN));
        }

        // Фильтр по рейтингу
        if (!empty($filters['rating_from'])) {
            $query->where('rating', '>=', (float) $filters['rating_from']);
        }

        return $query;
    }

    protected function applySorting(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating_desc' => $query->orderBy('rating', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    public function find(int $id)
    {
        return $this->model->with('category')->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return $this->model->create($data);
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->model->findOrFail($id);
            $product->update($data);
            return $product->fresh();
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $product = $this->model->findOrFail($id);
            return $product->delete();
        });
    }
}
