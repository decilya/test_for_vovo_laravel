<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Репозиторий продукта
 */
class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @param Product $model
     */
    public function __construct(
        protected Product $model
    ) {}

    /**
     * @param array $filters
     * @param string $sort
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function all(array $filters = [], string $sort = 'newest', int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $sort);

        return $query->paginate($perPage);
    }

    /**
     * @param array $filters
     * @param string $sort
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function withCategory(array $filters = [], string $sort = 'newest', int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with('category');

        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $sort);

        return $query->paginate($perPage);
    }

    /**
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
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

    /**
     * @param Builder $query
     * @param string $sort
     * @return Builder
     */
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

    /**
     * @param int $id
     * @return Product|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function find(int $id): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|Product|null
    {
        return $this->model->with('category')->findOrFail($id);
    }

    /**
     * @param array $data
     * @return mixed
     * @throws \Throwable
     */
    public function create(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            return $this->model->create($data);
        });
    }

    /**
     * @param int $id
     * @param array $data
     * @return mixed
     * @throws \Throwable
     */
    public function update(int $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->model->findOrFail($id);
            $product->update($data);
            return $product->fresh();
        });
    }

    /**
     * @param int $id
     * @return bool
     * @throws \Throwable
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $product = $this->model->findOrFail($id);
            return $product->delete();
        });
    }
}
