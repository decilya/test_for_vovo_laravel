<?php
// app/Http/Middleware/LoginThrottle.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class LoginThrottle
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response): void
    {
        // Логируем неудачную попытку после выполнения запроса
        if ($response->getStatusCode() === 401) { // Unauthorized
            $key = 'login_failures:' . $request->ip();
            $attempts = Cache::get($key, 0);

            // Увеличиваем счетчик неудач на 30 минут
            Cache::put($key, $attempts + 1, now()->addMinutes(30));

            // Если слишком много неудач - блокируем на час
            if ($attempts + 1 >= 10) {
                Cache::put('login_blocked:' . $request->ip(), true, now()->addHour());
            }
        }

        // Сбрасываем счетчик при успешном входе
        if ($response->getStatusCode() === 200 &&
            str_contains($request->path(), 'login')) {
            Cache::forget('login_failures:' . $request->ip());
            Cache::forget('login_blocked:' . $request->ip());
        }
    }
}
