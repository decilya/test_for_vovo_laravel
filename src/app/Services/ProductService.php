<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Eloquent\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Сервисный слой для работы с продуктами
 */
class ProductService
{
    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    /**
     * @param array $filters
     * @param string $sort
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFilteredProducts(array $filters = [], string $sort = 'newest', int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->withCategory($filters, $sort, $perPage);
    }

    /**
     * @param int $id
     * @return Model|null
     */
    public function getProduct(int $id): ?Model
    {
        return $this->productRepository->find($id);
    }

    /**
     * @param array $data
     * @return Model
     * @throws \Throwable
     */
    public function createProduct(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            return $this->productRepository->create($data);
        });
    }

    /**
     * @param int $id
     * @param array $data
     * @return Model
     * @throws \Throwable
     */
    public function updateProduct(int $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data) {
            return $this->productRepository->update($id, $data);
        });
    }

    /**
     * @param int $id
     * @return bool
     * @throws \Throwable
     */
    public function deleteProduct(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            return $this->productRepository->delete($id);
        });
    }
}
