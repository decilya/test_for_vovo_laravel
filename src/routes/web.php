<?php
use App\Http\Controllers\API\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\LogAuthAttempts;

// Главная страница
Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::prefix('products')->name('products.')->group(function () {
    // Основной поиск с фильтрами (GET /api/v1/products)
    Route::get('/', [ProductController::class, 'index'])->name('index');
});



// Логируем все аутентификационные запросы
Route::middleware(['log.auth', 'throttle:10,1'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail']);
});

// API роуты с расширенным логированием
Route::prefix('api/v1')->middleware([
    'api',
    'log.auth',
    'throttle:60,1',
    \App\Http\Middleware\DetectSuspiciousActivity::class
])->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});
