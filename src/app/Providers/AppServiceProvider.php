<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Регистрация сервисов приложения
     *
     * @return void
     */
    public function register(): void
    {
        try {
            // Регистрация Telescope только для локальной среды
            if ($this->app->environment('local') && class_exists('Laravel\Telescope\TelescopeServiceProvider')) {
                $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
                $this->app->register(TelescopeServiceProvider::class);
            }

            // Регистрация своих сервисов
            $this->registerCustomServices();

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (регистрация сервисов приложения):
                Ошибка в методе register: ' . $e->getMessage());
        }
    }

    /**
     * Загрузка сервисов приложения
     *
     * @return void
     */
    public function boot(): void
    {
        try {
            // Настройка ограничения запросов
            $this->configureRateLimiting();

            // Настройка логирования безопасности
            $this->configureSecurityLogging();

            // Настройка авторизации для Telescope
            $this->configureTelescopeAuthorization();

            // Настройки для продакшена
            $this->configureForProduction();

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (загрузка сервисов приложения):
                    Ошибка в методе boot: ' . $e->getMessage());
        }
    }

    /**
     * Регистрация кастомных сервисов
     *
     * @return void
     */
    protected function registerCustomServices(): void
    {
        try {
            // Сервис Telegram для уведомлений безопасности
            $this->app->singleton('telegram.security', function ($app) {
                return new \App\Services\TelegramSecurityService();
            });

            // Сервис геолокации IP
            $this->app->singleton('ip.geolocation', function ($app) {
                return new \App\Services\IpGeolocationService();
            });

            // Сервис мониторинга безопасности
            $this->app->singleton('security.monitor', function ($app) {
                return new \App\Services\SecurityMonitorService();
            });

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (регистрация кастомных сервисов):
                Ошибка в методе registerCustomServices: ' . $e->getMessage());
        }
    }

    /**
     * Настройка авторизации для Telescope
     *
     * @return void
     */
    protected function configureTelescopeAuthorization(): void
    {
        try {
            // Разрешаем доступ к Telescope только администраторам
            Gate::define('viewTelescope', function ($user = null) {
                // В локальной среде разрешаем всем
                if (app()->environment('local')) {
                    return true;
                }

                // В продакшене только определенным пользователям
                if (!$user) {
                    return false;
                }

                // Проверяем email или роль
                return in_array($user->email, [
                    'admin@example.com',
                    // Добавьте другие email администраторов
                ]);
            });

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (настройка авторизации для Telescope):
                Ошибка в методе configureTelescopeAuthorization: ' . $e->getMessage());
        }
    }

    /**
     * Настройка ограничения запросов (Rate Limiting)
     *
     * @return void
     */
    protected function configureRateLimiting(): void
    {
        try {
            // Глобальный лимит для API
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
            });

            // Адаптивный лимит для входа
            RateLimiter::for('adaptive_login', function (Request $request) {
                return $this->getAdaptiveLoginLimit($request);
            });

            // Строгий лимит для регистрации
            RateLimiter::for('strict_register', function (Request $request) {
                return Limit::perHour(3)
                    ->by($request->ip() . '|' . ($request->input('email') ?? ''))
                    ->response(function (Request $request, array $headers) {
                        return response()->json([
                            'message' => 'Слишком много попыток регистрации. Попробуйте через час.',
                            'retry_after' => $headers['Retry-After'] ?? 3600,
                        ], 429);
                    });
            });

            // Лимит для восстановления пароля
            RateLimiter::for('password_reset', function (Request $request) {
                return Limit::perHour(5)
                    ->by($request->ip())
                    ->response(function (Request $request, array $headers) {
                        return response()->json([
                            'message' => 'Слишком много запросов на восстановление пароля.',
                            'retry_after' => $headers['Retry-After'] ?? 3600,
                        ], 429);
                    });
            });

            // Лимит для Telescope (защита от злоупотреблений)
            RateLimiter::for('telescope', function (Request $request) {
                return Limit::perMinute(30)->by($request->ip());
            });

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (настройка ограничения запросов):
                Ошибка в методе configureRateLimiting: ' . $e->getMessage());
        }
    }

    /**
     * Получение адаптивного лимита для входа
     *
     * @param Request $request HTTP запрос
     * @return Limit Адаптивный лимит запросов
     */
    private function getAdaptiveLoginLimit(Request $request): Limit
    {
        try {
            // Определяем лимиты в зависимости от количества неудачных попыток
            $failedAttempts = Cache::get('login_failures:' . $request->ip(), 0);

            return match (true) {
                $failedAttempts >= 10 => Limit::perHour(1)
                    ->by($request->ip() . '|' . $request->input('email', ''))
                    ->response(function () use ($failedAttempts) {
                        return response()->json([
                            'message' => 'Слишком много неудачных попыток. Ваш IP временно заблокирован.',
                            'retry_after' => 3600,
                            'failed_attempts' => $failedAttempts,
                        ], 429);
                    }),

                $failedAttempts >= 5 => Limit::perMinutes(15, 2)
                    ->by($request->ip())
                    ->response(function () use ($failedAttempts) {
                        return response()->json([
                            'message' => 'Обнаружено много неудачных попыток. Подождите 15 минут.',
                            'retry_after' => 900,
                            'failed_attempts' => $failedAttempts,
                        ], 429);
                    }),

                $failedAttempts >= 3 => Limit::perMinutes(5, 5)
                    ->by($request->ip())
                    ->response(function () use ($failedAttempts) {
                        return response()->json([
                            'message' => 'Несколько неудачных попыток. Подождите 5 минут.',
                            'retry_after' => 300,
                            'failed_attempts' => $failedAttempts,
                            'requires_captcha' => true,
                        ], 429);
                    }),

                default => Limit::perMinute(10)->by($request->ip())
            };

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (получение адаптивного лимита для входа):
                Ошибка в методе getAdaptiveLoginLimit: ' . $e->getMessage());
            return Limit::perMinute(10)->by($request->ip());
        }
    }

    /**
     * Настройка логирования безопасности
     *
     * @return void
     */
    protected function configureSecurityLogging(): void
    {
        try {
            // Неудачные попытки входа
            Event::listen(Failed::class, function (Failed $event) {
                $this->logFailedLogin($event);
            });

            // Блокировки
            Event::listen(Lockout::class, function (Lockout $event) {
                $this->logLockout($event);
            });

            // Успешные входы
            Event::listen(Login::class, function (Login $event) {
                $this->logSuccessfulLogin($event);
            });

            // Логирование SQL-запросов в продакшене при ошибках
            if ($this->app->environment('production')) {
                Event::listen('Illuminate\Database\Events\QueryExecuted', function ($query) {
                    if ($query->time > 1000) { // Запросы дольше 1 секунды
                        Log::channel('security')->warning('Медленный SQL запрос', [
                            'sql' => $query->sql,
                            'bindings' => $query->bindings,
                            'time' => $query->time,
                            'connection' => $query->connectionName,
                        ]);
                    }
                });
            }

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (настройка логирования безопасности):
                Ошибка в методе configureSecurityLogging: ' . $e->getMessage());
        }
    }

    /**
     * Логирование неудачной попытки входа
     *
     * @param Failed $event Событие неудачного входа
     * @return void
     */
    private function logFailedLogin(Failed $event): void
    {
        try {
            $request = request();
            $ip = $request->ip();
            $email = $event->credentials['email'] ?? 'unknown';

            // Увеличиваем счетчик неудачных попыток
            $key = 'login_failures:' . $ip;
            $attempts = Cache::get($key, 0) + 1;
            Cache::put($key, $attempts, now()->addHour()); // Храним 1 час

            // Логируем в security канал
            Log::channel('security')->warning('Неудачная попытка входа', [
                'event' => 'auth.failed',
                'ip' => $ip,
                'email' => $email,
                'attempts' => $attempts,
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String(),
                'country' => $this->getCountryByIP($ip),
                'url' => $request->fullUrl(),
            ]);

            // Проверяем на подозрительную активность
            if ($attempts >= 5) {
                $this->logSuspiciousActivity($ip, $email, $attempts);
            }

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (логирование неудачной попытки входа):
                Ошибка в методе logFailedLogin: ' . $e->getMessage());
        }
    }

    /**
     * Логирование блокировки
     *
     * @param Lockout $event Событие блокировки
     * @return void
     */
    private function logLockout(Lockout $event): void
    {
        try {
            $request = request();
            $ip = $request->ip();

            // Получаем информацию о лимите из события
            $throttleKey = $this->extractThrottleKey($event);

            Log::channel('security')->alert('Сработала блокировка из-за превышения лимитов', [
                'event' => 'auth.lockout',
                'ip' => $ip,
                'throttle_key' => $throttleKey,
                'timestamp' => now()->toIso8601String(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);

            // Отправляем уведомление в Telegram
            $this->sendTelegramNotification('lockout', [
                'ip' => $ip,
                'user_agent' => substr($request->userAgent(), 0, 100),
                'timestamp' => now()->toDateTimeString(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (логирование блокировки):
                Ошибка в методе logLockout: ' . $e->getMessage());
        }
    }

    /**
     * Извлечение ключа throttle из события Lockout
     *
     * @param Lockout $event Событие блокировки
     * @return string Ключ throttle
     */
    private function extractThrottleKey(Lockout $event): string
    {
        try {
            // В Laravel 8+ и выше, событие Lockout имеет метод request()
            if (method_exists($event, 'request')) {
                $request = $event->request();

                // Пытаемся получить информацию о лимите из RateLimiter
                $throttleKey = $request->ip();

                // Проверяем разные возможные ключи
                if ($request->has('email')) {
                    $throttleKey .= '|' . $request->input('email');
                }

                return $throttleKey;
            }

            // Альтернативный способ через рефлексию
            $reflection = new \ReflectionClass($event);
            if ($reflection->hasProperty('request')) {
                $property = $reflection->getProperty('request');
                $property->setAccessible(true);
                $request = $property->getValue($event);

                return $request->ip() ?? 'unknown';
            }

            return 'unknown';

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (извлечение ключа throttle из события Lockout):
                                      Ошибка в методе extractThrottleKey: ' . $e->getMessage());
            return 'unknown';
        }
    }

    /**
     * Логирование успешного входа
     *
     * @param Login $event Событие успешного входа
     * @return void
     */
    private function logSuccessfulLogin(Login $event): void
    {
        try {
            $user = $event->user;
            $ip = request()->ip();

            // Сбрасываем счетчик неудачных попыток
            Cache::forget('login_failures:' . $ip);

            // Логируем успешный вход
            Log::channel('security')->info('Успешный вход в систему', [
                'event' => 'auth.success',
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $ip,
                'timestamp' => now()->toIso8601String(),
                'country' => $this->getCountryByIP($ip),
                'user_agent' => request()->userAgent(),
            ]);

            // Обновляем последний IP пользователя
            $this->updateUserLastLogin($user, $ip);

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (логирование успешного входа):
                    Ошибка в методе logSuccessfulLogin: ' . $e->getMessage());
        }
    }

    /**
     * Логирование подозрительной активности
     *
     * @param string $ip IP адрес
     * @param string $email Email пользователя
     * @param int $attempts Количество попыток
     * @return void
     */
    private function logSuspiciousActivity(string $ip, string $email, int $attempts): void
    {
        try {
            $data = [
                'event' => 'auth.suspicious',
                'ip' => $ip,
                'email' => $email,
                'attempts' => $attempts,
                'timestamp' => now()->toIso8601String(),
                'country' => $this->getCountryByIP($ip),
                'risk_level' => $attempts >= 10 ? 'high' : 'medium',
            ];

            Log::channel('security')->critical('Обнаружена подозрительная активность', $data);

            // Отправляем критическое уведомление
            $this->sendTelegramNotification('suspicious_activity', $data);

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (логирование подозрительной активности):
                    Ошибка в методе logSuspiciousActivity: ' . $e->getMessage());
        }
    }

    /**
     * Получение страны по IP
     *
     * @param string $ip IP адрес
     * @return string|null Название страны или null в случае ошибки
     */
    private function getCountryByIP(string $ip): ?string
    {
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return 'Локальный';
        }

        $cacheKey = 'ip_country:' . md5($ip);

        try {
            return Cache::remember($cacheKey, now()->addDays(7), function () use ($ip) {
                return $this->fetchCountryFromAPI($ip);
            });

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (получение страны по IP):
                    Ошибка в методе getCountryByIP: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Получение страны из API
     *
     * @param string $ip IP адрес
     * @return string|null Название страны или null в случае ошибки
     */
    private function fetchCountryFromAPI(string $ip): ?string
    {
        try {
            // Первый вариант: ip-api.com (бесплатно)
            $response = Http::timeout(2)
                ->get("http://ip-api.com/json/{$ip}?fields=country,countryCode,status");

            if ($response->successful()) {
                $data = $response->json();
                if ($data['status'] === 'success') {
                    return $data['country'] ?? $data['countryCode'] ?? null;
                }
            }

            // Второй вариант: ipapi.co (бесплатно 1000 запросов/месяц)
            $response = Http::timeout(2)
                ->get("https://ipapi.co/{$ip}/country_name/");

            if ($response->successful() && $response->body() !== 'Undefined') {
                $country = trim($response->body());
                return !empty($country) ? $country : null;
            }

            return null;

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (получение страны из API):
                    Ошибка в методе fetchCountryFromAPI: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Обновление информации о последнем входе пользователя
     *
     * @param mixed $user Объект пользователя
     * @param string $ip IP адрес
     * @return void
     */
    private function updateUserLastLogin($user, string $ip): void
    {
        try {
            // Сохраняем в кеш последний IP пользователя
            $lastLoginKey = 'last_login:' . $user->id;
            $lastLoginIp = Cache::get($lastLoginKey);

            // Проверяем, не изменилась ли страна
            if ($lastLoginIp) {
                $lastCountry = $this->getCountryByIP($lastLoginIp);
                $currentCountry = $this->getCountryByIP($ip);

                if ($lastCountry && $currentCountry && $lastCountry !== $currentCountry) {
                    Log::channel('security')->warning('Вход с нового местоположения', [
                        'user_id' => $user->id,
                        'previous_country' => $lastCountry,
                        'current_country' => $currentCountry,
                        'previous_ip' => $lastLoginIp,
                        'current_ip' => $ip,
                    ]);
                }
            }

            // Обновляем последний IP
            Cache::put($lastLoginKey, $ip, now()->addDays(30));

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (обновление информации о последнем входе пользователя):
                    Ошибка в методе updateUserLastLogin: ' . $e->getMessage());
        }
    }

    /**
     * Отправка уведомления в Telegram
     *
     * @param string $type Тип уведомления
     * @param array $data Данные для уведомления
     * @return void
     */
    private function sendTelegramNotification(string $type, array $data): void
    {
        try {
            if (!$this->app->bound('telegram.security')) {
                Log::warning('AppServiceProvider: Сервис telegram.security не зарегистрирован');
                return;
            }

            $telegram = $this->app->make('telegram.security');

            $message = $this->formatTelegramMessage($type, $data);

            $telegram->sendSecurityAlert($message);

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (отправка уведомления в Telegram):
                    Ошибка в методе sendTelegramNotification: ' . $e->getMessage());
        }
    }

    /**
     * Форматирование сообщения для Telegram
     *
     * @param string $type Тип сообщения
     * @param array $data Данные для сообщения
     * @return string Отформатированное сообщение
     */
    private function formatTelegramMessage(string $type, array $data): string
    {
        try {
            $messages = [
                'lockout' => "🔒 <b>Блокировка аккаунта</b>\n\n"
                    . "<b>IP:</b> <code>{$data['ip']}</code>\n"
                    . "<b>Время:</b> {$data['timestamp']}\n"
                    . "<b>URL:</b> {$data['url']}\n"
                    . "<b>Метод:</b> {$data['method']}\n"
                    . "<b>User Agent:</b>\n<code>{$data['user_agent']}</code>",

                'suspicious_activity' => "🚨 <b>Подозрительная активность</b>\n\n"
                    . "<b>Уровень риска:</b> {$data['risk_level']}\n"
                    . "<b>IP:</b> <code>{$data['ip']}</code>\n"
                    . "<b>Email:</b> <code>{$data['email']}</code>\n"
                    . "<b>Попытки:</b> {$data['attempts']}\n"
                    . "<b>Страна:</b> " . ($data['country'] ?? 'Неизвестно') . "\n"
                    . "<b>Время:</b> {$data['timestamp']}",
            ];

            return $messages[$type] ?? "⚠️ <b>Уведомление безопасности</b>\n\n"
            . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (форматирование сообщения для Telegram): Ошибка в методе formatTelegramMessage: ' . $e->getMessage());
            return "⚠️ <b>Ошибка формирования уведомления</b>\n\nТип: {$type}";
        }
    }

    /**
     * Настройки для продакшена
     *
     * @return void
     */
    protected function configureForProduction(): void
    {
        try {
            if ($this->app->environment('production')) {
                // Принудительное использование HTTPS
                if (config('app.force_https', false)) {
                    URL::forceScheme('https');
                    $this->app['request']->server->set('HTTPS', true);
                }

                // Настройка Telescope для продакшена
                $this->configureTelescopeForProduction();
            }

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (настройки для продакшена):
                Ошибка в методе configureForProduction: ' . $e->getMessage());
        }
    }

    /**
     * Настройка Telescope для продакшена
     *
     * @return void
     */
    protected function configureTelescopeForProduction(): void
    {
        try {
            // Ограничиваем запись данных в Telescope в продакшене
            config([
                'telescope.storage.database.connection' => 'mysql',
                'telescope.enabled' => true,
                'telescope.record' => [
                    \Laravel\Telescope\EntryType::QUERY => false, // Не записываем все SQL
                    \Laravel\Telescope\EntryType::REQUEST => true,
                    \Laravel\Telescope\EntryType::EXCEPTION => true,
                    \Laravel\Telescope\EntryType::LOG => false,
                    \Laravel\Telescope\EntryType::DUMP => false,
                    \Laravel\Telescope\EntryType::SCHEDULED_TASK => true,
                    \Laravel\Telescope\EntryType::JOB => false,
                    \Laravel\Telescope\EntryType::MAIL => false,
                    \Laravel\Telescope\EntryType::NOTIFICATION => false,
                    \Laravel\Telescope\EntryType::GATE => false,
                    \Laravel\Telescope\EntryType::MODEL => false,
                    \Laravel\Telescope\EntryType::REDIS => false,
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('AppServiceProvider (настройка Telescope для продакшена):
                    Ошибка в методе configureTelescopeForProduction: ' . $e->getMessage());
        }
    }
}
