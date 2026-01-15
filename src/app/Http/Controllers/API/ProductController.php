<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductFilterRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

/**
 * Controller для работы с продуктами
 */
class ProductController extends Controller
{
    /**
     * @param ProductService $productService
     */
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * HTTP-endpoint (например, GET /api/products),
     * который возвращает список товаров с возможностью фильтрации и сортировки.
     *
     * @param ProductFilterRequest $request
     * @return JsonResponse
     */
    public function index(ProductFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $sort = $request->get('sort', 'newest');
        $perPage = $request->get('per_page', 15);

        $products = $this->productService->getFilteredProducts($filters, $sort, $perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], 200); // Явно укажем для примера код, хотя по умолчанию и так будет 200.
    }
}
