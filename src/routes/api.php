<?php

use App\Http\Controllers\API\{
    ProductController,
    CategoryController,
    AuthController,
    SearchController
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Группа публичных маршрутов
Route::prefix('v1')->group(function () {

    // Публичные маршруты для товаров
    Route::prefix('products')->name('products.')->group(function () {
        // Основной поиск с фильтрами (GET /api/v1/products)
        Route::get('/', [ProductController::class, 'index'])->name('index');
    });

});

// API версия 2 (задел на будущее)
Route::prefix('v2')->group(function () {
    Route::get('products', function () {
        return response()->json([
            'message' => 'API v2 is coming soon',
            'endpoint' => 'GET /api/v2/products'
        ]);
    });
});

// Fallback route для API
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found. Please check the documentation.',
        'documentation' => url('/api/docs'),
        'current_version' => 'v1',
        'available_versions' => ['v1'],
    ], 404);
});
