<?php

namespace App\Http\Middleware;

use App\Service\TelegramService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class MonitorResponseTime
{
    const SERVICE_NAME = 'backend';

    public function __construct(protected TelegramService $telegramService)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $response = $next($request);

        $this->logResponseTime($request, $response, $startTime);
        return $response;
    }

    protected function logResponseTime(Request $request, $response, float $startTime): void
    {
        if (App::hasDebugModeEnabled()) {
            return;
        }
        $duration = (microtime(true) - $startTime) * 1000; // в миллисекундах
        $routeName = $request->route()->getName();

        $maxResponseTime = Cache::store('redis')->remember('request_max_response_time_' . self::SERVICE_NAME . '_' . $routeName,
            60 * 30,
            function() use ($routeName) {
                return (int)DB::table('request_metrics')
                    ->select(['max_response_time'])
                    ->where('service', self::SERVICE_NAME)
                    ->where('name', $routeName)
                    ->first()?->max_response_time;
            });

        if ($maxResponseTime && $duration > $maxResponseTime) {
            // Проверяем, не превышен ли лимит
            if (RateLimiter::tooManyAttempts('send-response-time-message:' .$routeName, 3)) {
                return;
            }

            $this->telegramService->sendMessageAsync(
                "Превышено максимальное время ответа для эндпоинта API $routeName.\n".
                'URL: ' . $request->url() . "\n" .
                      'Method: ' . $request->method() . "\n".
                intval($duration) ." мс > $maxResponseTime мс\n"
            );

            // Увеличиваем счетчик
            RateLimiter::hit('send-response-time-message:' .$routeName, 600); // 600 секунд = 10 минут
        }
    }
}
