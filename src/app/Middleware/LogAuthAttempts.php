<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogAuthAttempts
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($this->shouldLogRequest($request)) {
            $this->logAuthRequest($request, $response);
        }
    }

    protected function shouldLogRequest(Request $request): bool
    {
        $authPaths = ['/login', '/register', '/password/reset', '/2fa'];
        $method = $request->method();

        return $method === 'POST' && in_array($request->path(), $authPaths);
    }

    protected function logAuthRequest(Request $request, Response $response): void
    {
        $logData = [
            'timestamp' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'user_agent' => $request->userAgent(),
            'content_type' => $request->header('content-type'),
            'country' => $this->getCountryFromIP($request->ip()),
            'request_id' => $request->header('x-request-id') ?? uniqid(),
            'headers' => [
                'x-forwarded-for' => $request->header('x-forwarded-for'),
                'referer' => $request->header('referer'),
                'origin' => $request->header('origin'),
            ],
        ];

        // Для безопасности скрываем пароли
        $filteredInput = $request->except(['password', 'password_confirmation', 'current_password']);
        $logData['input'] = $filteredInput;

        // Определяем уровень логирования
        $level = $this->getLogLevel($response, $request);

        Log::channel('security')->log($level, 'Authentication request', $logData);

        // Дополнительно логируем подозрительные запросы
        if ($this->isSuspiciousRequest($request, $response)) {
            $this->logSuspiciousDetails($request, $response, $logData);
        }
    }

    protected function getLogLevel(Response $response, Request $request): string
    {
        if ($response->getStatusCode() === 401 || $response->getStatusCode() === 429) {
            return 'warning';
        }

        if ($response->getStatusCode() >= 500) {
            return 'error';
        }

        // Проверяем на аномалии
        if ($this->hasAnomalies($request)) {
            return 'alert';
        }

        return 'info';
    }

    protected function hasAnomalies(Request $request): bool
    {
        // Проверяем аномальные user-agent
        $userAgent = $request->userAgent();
        $suspiciousAgents = [
            'curl', 'wget', 'python', 'java', 'go-http',
            'mass', 'scanner', 'nikto', 'sqlmap'
        ];

        foreach ($suspiciousAgents as $suspicious) {
            if (stripos($userAgent, $suspicious) !== false) {
                return true;
            }
        }

        // Проверяем слишком длинные email
        $email = $request->input('email', '');
        if (strlen($email) > 100) {
            return true;
        }

        // Проверяем необычные заголовки
        if ($request->header('x-attack') || $request->header('x-scan')) {
            return true;
        }

        return false;
    }

    protected function isSuspiciousRequest(Request $request, Response $response): bool
    {
        // Множественные быстрые запросы
        $key = 'request_count:' . $request->ip();
        $count = cache()->increment($key, 1, now()->addMinute());

        if ($count > 30) {
            return true;
        }

        // Запросы с разных стран за короткое время
        $country = $this->getCountryFromIP($request->ip());
        $countryKey = 'country_changes:' . ($request->input('email') ?? $request->ip());
        $countries = cache()->get($countryKey, []);

        if (!in_array($country, $countries)) {
            $countries[] = $country;
            cache()->put($countryKey, $countries, now()->addHour());

            if (count($countries) > 3) {
                return true;
            }
        }

        return false;
    }

    protected function logSuspiciousDetails(Request $request, Response $response, array $logData): void
    {
        $suspiciousData = array_merge($logData, [
            'suspicion_reason' => 'Multiple indicators detected',
            'risk_score' => $this->calculateRiskScore($request),
            'action_taken' => 'logged',
            'recommendation' => 'Consider temporary IP block',
        ]);

        Log::channel('security')->alert('SUSPICIOUS ACTIVITY DETECTED', $suspiciousData);

        // Можно отправлять уведомления
        $this->notifyAdmins($suspiciousData);
    }

    protected function calculateRiskScore(Request $request): int
    {
        $score = 0;

        // User agent
        $ua = strtolower($request->userAgent());
        if (str_contains($ua, 'bot')) $score += 20;
        if (str_contains($ua, 'scanner')) $score += 30;
        if (str_contains($ua, 'curl')) $score += 10;

        // Частота запросов
        $freqKey = 'freq:' . $request->ip();
        $freq = cache()->get($freqKey, 0);
        if ($freq > 50) $score += 25;

        // Попытки к несуществующим эндпоинтам
        $path = $request->path();
        if (preg_match('/\.(php|asp|aspx|jsp)/i', $path)) {
            $score += 50;
        }

        return min($score, 100);
    }

    protected function getCountryFromIP(string $ip): string
    {
        try {
            // Используем сервис или локальную БД
            if ($ip === '127.0.0.1') {
                return 'localhost';
            }

            // Для простоты используем кэш
            $cacheKey = 'ip_country:' . $ip;
            return cache()->remember($cacheKey, 3600, function () use ($ip) {
                // В реальном проекте здесь будет запрос к API или БД
                // Например, через maxmind/geoip2 или ip2location
                return 'Unknown';
            });
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    protected function notifyAdmins(array $data): void
    {
        // Отправка в Slack/Telegram/Email
        // Пример для Slack
        if (config('logging.security_notifications.slack_webhook')) {
            \Illuminate\Support\Facades\Http::post(
                config('logging.security_notifications.slack_webhook'),
                [
                    'text' => "🚨 Подозрительная активность обнаружена",
                    'attachments' => [[
                        'title' => 'Детали',
                        'fields' => [
                            ['title' => 'IP', 'value' => $data['ip'], 'short' => true],
                            ['title' => 'Метод', 'value' => $data['method'], 'short' => true],
                            ['title' => 'Риск', 'value' => $data['risk_score'] . '/100', 'short' => true],
                            ['title' => 'Время', 'value' => $data['timestamp'], 'short' => true],
                        ]
                    ]]
                ]
            );
        }
    }
}
