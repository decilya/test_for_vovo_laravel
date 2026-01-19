<?php

use App\Http\Controllers\API\{
    ProductController,
    AuthController,
};

use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
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

    Route::get('/login', [AuthController::class, 'login'])->withoutMiddleware(['auth:sanctum']);

    Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware(['auth:sanctum']);
    Route::post('/register', [AuthController::class, 'register'])->withoutMiddleware(['auth:sanctum']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    // Публичные маршруты для товаров
    Route::prefix('products')->name('products.')->group(function () {
        // Основной поиск с фильтрами (GET /api/v1/products)
        Route::get('/', [ProductController::class, 'index'])->name('index');
    });

    // Auth routes
    //  Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    RateLimiter::for('register', function (Request $request) {
        return Limit::perHour(3)
            ->by($request->ip())
            ->response(function () {
                return response()->json([
                    'message' => 'Too many registration attempts',
                    'retry_after' => 3600
                ], 429);
            });
    });


  /*  Route::post('/login', [AuthController::class, 'login'])
        ->middleware(['throttle:adaptive_login']);

    Route::get('/register', [AuthController::class, 'register'])->name('auth.register');

    Route::get('/login', [AuthController::class, 'login'])->name('auth.login')
        ->middleware(['throttle:adaptive_login']); */

// Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'me'])->name('auth.user');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
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

Route::get('/test', function() {
    return response()->json([
        'message' => 'API работает',
        'timestamp' => now(),
        'memcached' => class_exists('Memcached'),
        'session_driver' => config('session.driver'),
    ]);
});

Route::get('/test-auth', function() {
    try {
        $user = \App\Models\User::where('email', 'admin@example.com')->first();
        return response()->json([
            'user_exists' => (bool)$user,
            'password_match' => $user && \Hash::check('password123', $user->password),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
        ], 500);
    }
});
