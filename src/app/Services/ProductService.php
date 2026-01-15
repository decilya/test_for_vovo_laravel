<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    public function getFilteredProducts(array $filters = [], string $sort = 'newest', int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->withCategory($filters, $sort, $perPage);
    }

    public function getProduct(int $id): ?Model
    {
        return $this->productRepository->find($id);
    }

    public function createProduct(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            return $this->productRepository->create($data);
        });
    }

    public function updateProduct(int $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data) {
            return $this->productRepository->update($id, $data);
        });
    }

    public function deleteProduct(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            return $this->productRepository->delete($id);
        });
    }
}
