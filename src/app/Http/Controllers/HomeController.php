<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Главная страница
     */
    public function index(): View
    {
        $apiRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(function ($route) {
                return str_starts_with($route->uri(), 'api/');
            })
            ->map(function ($route) {
                return [
                    'uri' => $route->uri(),
                    'methods' => $route->methods(),
                    'name' => $route->getName(),
                ];
            });

        return view('welcome', [
            'apiRoutes' => $apiRoutes,
            'appName' => config('app.name'),
            'laravelVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
        ]);
    }

    /**
     * Проверка работоспособности
     */
    public function health(): array
    {
        return [
            'status' => 'ok',
            'timestamp' => now()->toDateTimeString(),
            'service' => 'Comment System API',
            'version' => '1.0.0',
        ];
    }
}
