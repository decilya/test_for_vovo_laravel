<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Соглашение на реализацию ProductRepository
 */
interface ProductRepositoryInterface
{
    public function all(array $filters = [], string $sort = 'newest', int $perPage = 15): LengthAwarePaginator;

    public function find(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id);

    public function withCategory(array $filters = [], string $sort = 'newest', int $perPage = 15): LengthAwarePaginator;
}
