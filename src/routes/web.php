<?php
use App\Http\Controllers\API\ProductController;
use Illuminate\Support\Facades\Route;

// Главная страница
Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::prefix('products')->name('products.')->group(function () {
    // Основной поиск с фильтрами (GET /api/v1/products)
    Route::get('/', [ProductController::class, 'index'])->name('index');
});

