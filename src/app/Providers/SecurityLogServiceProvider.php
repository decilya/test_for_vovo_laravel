<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Сервис-провайдер для логирования событий безопасности и мониторинга подозрительной активности
 *
 * Этот провайдер отвечает за:
 * - Логирование неудачных попыток входа
 * - Обнаружение и логирование подозрительной активности
 * - Интеграцию с геолокацией IP адресов
 * - Настройку кастомных обработчиков исключений безопасности
 *
 * @package App\Providers
 */
class SecurityLogServiceProvider extends ServiceProvider
{
    /**
     * Регистрация событий безопасности и кастомного логирования
     *
     * Метод инициализирует систему мониторинга безопасности:
     * 1. Регистрирует обработчики событий аутентификации
     * 2. Настраивает кастомные обработчики исключений
     * 3. Регистрирует middleware для логирования
     *
     * @return void
     */
    public function boot(): void
    {
        try {
            // Регистрация обработчиков событий безопасности
            $this->registerSecurityEvents();

            // Регистрация кастомного логирования
            $this->registerCustomLogging();

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (регистрация событий безопасности и кастомного логирования): Ошибка в методе boot: ' . $e->getMessage());
        }
    }

    /**
     * Регистрация обработчиков для событий безопасности
     *
     * Метод настраивает обработчики для:
     * - Неудачных попыток входа
     * - Блокировок из-за превышения лимитов
     * - Кастомных событий подозрительной активности
     *
     * @return void
     */
    protected function registerSecurityEvents(): void
    {
        try {
            // Обработчик неудачных попыток входа
            Event::listen(Failed::class, function ($event) {
                $this->handleFailedLoginEvent($event);
            });

            // Обработчик блокировок из-за rate limiting
            Event::listen(Lockout::class, function ($event) {
                $this->handleLockoutEvent($event);
            });

            // Обработчик кастомных событий подозрительной активности
            Event::listen('auth.suspicious', function ($data) {
                $this->handleSuspiciousActivityEvent($data);
            });

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (регистрация обработчиков для событий безопасности): Ошибка в методе registerSecurityEvents: ' . $e->getMessage());
        }
    }

    /**
     * Обработка события неудачной попытки входа
     *
     * @param Failed $event Объект события неудачного входа
     * @return void
     */
    protected function handleFailedLoginEvent($event): void
    {
        try {
            Log::channel('security')->warning('Обнаружена неудачная попытка входа в систему', [
                'event' => 'auth.failed',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'email' => $event->credentials['email'] ?? 'неизвестно',
                'timestamp' => now()->toIso8601String(),
                'headers' => [
                    'x-forwarded-for' => request()->header('x-forwarded-for'),
                    'referer' => request()->header('referer'),
                ]
            ]);

            // Регистрация подозрительной активности
            $this->logSuspiciousActivity($event);

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (обработка события неудачной попытки входа): Ошибка в методе handleFailedLoginEvent: ' . $e->getMessage());
        }
    }

    /**
     * Обработка события блокировки из-за превышения лимитов
     *
     * @param Lockout $event Объект события блокировки
     * @return void
     */
    protected function handleLockoutEvent($event): void
    {
        try {
            Log::channel('security')->alert('Сработала блокировка из-за превышения лимитов запросов', [
                'event' => 'auth.lockout',
                'ip' => request()->ip(),
                'throttle_key' => $this->extractThrottleKeyFromEvent($event),
                'timestamp' => now()->toIso8601String(),
                'user_agent' => request()->userAgent(),
            ]);

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (обработка события блокировки из-за превышения лимитов): Ошибка в методе handleLockoutEvent: ' . $e->getMessage());
        }
    }

    /**
     * Обработка события подозрительной активности
     *
     * @param array $data Данные о подозрительной активности
     * @return void
     */
    protected function handleSuspiciousActivityEvent($data): void
    {
        try {
            Log::channel('security')->critical('Зафиксирована подозрительная активность в системе аутентификации', $data);

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (обработка события подозрительной активности): Ошибка в методе handleSuspiciousActivityEvent: ' . $e->getMessage());
        }
    }

    /**
     * Извлечение ключа throttle из события блокировки
     *
     * @param Lockout $event Объект события блокировки
     * @return string Ключ throttle или 'неизвестно'
     */
    protected function extractThrottleKeyFromEvent(Lockout $event): string
    {
        try {
            // Для Laravel 8+ используем метод request()
            if (method_exists($event, 'request')) {
                $request = $event->request();
                return $request->ip() ?? 'неизвестно';
            }

            // Альтернативный способ через рефлексию
            $reflection = new \ReflectionClass($event);
            if ($reflection->hasProperty('request')) {
                $property = $reflection->getProperty('request');
                $property->setAccessible(true);
                $request = $property->getValue($event);

                return $request->ip() ?? 'неизвестно';
            }

            return 'неизвестно';

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (извлечение ключа throttle из события блокировки): Ошибка в методе extractThrottleKeyFromEvent: ' . $e->getMessage());
            return 'неизвестно';
        }
    }

    /**
     * Логирование подозрительной активности на основе события неудачного входа
     *
     * @param Failed $event Объект события неудачного входа
     * @return void
     */
    protected function logSuspiciousActivity($event): void
    {
        try {
            $ip = request()->ip();
            $email = $event->credentials['email'] ?? 'неизвестно';

            // Ключи для хранения статистики в кэше
            $ipKey = "suspicious:ip:" . md5($ip);
            $emailKey = "suspicious:email:" . md5($email);

            // Увеличиваем счетчики попыток
            $ipAttempts = Cache::get($ipKey, 0) + 1;
            $emailAttempts = Cache::get($emailKey, 0) + 1;

            // Сохраняем обновленные счетчики
            Cache::put($ipKey, $ipAttempts, now()->addHour());
            Cache::put($emailKey, $emailAttempts, now()->addHour());

            // Логируем подозрительную активность при превышении порогов
            if ($ipAttempts >= 10) {
                $this->logSuspiciousIpActivity($ip, $ipAttempts, $email);
            }

            if ($emailAttempts >= 5) {
                $this->logSuspiciousEmailActivity($email, $emailAttempts, $ip);
            }

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (логирование подозрительной активности на основе события неудачного входа): Ошибка в методе logSuspiciousActivity: ' . $e->getMessage());
        }
    }

    /**
     * Логирование подозрительной активности по IP адресу
     *
     * @param string $ip IP адрес
     * @param int $attempts Количество попыток
     * @param string $email Email пользователя
     * @return void
     */
    protected function logSuspiciousIpActivity(string $ip, int $attempts, string $email): void
    {
        try {
            Log::channel('security')->alert('Обнаружен подозрительный IP адрес', [
                'event' => 'suspicious.ip',
                'ip' => $ip,
                'attempts' => $attempts,
                'email' => $email,
                'user_agent' => request()->userAgent(),
                'country' => $this->getCountryByIP($ip),
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (логирование подозрительной активности по IP адресу): Ошибка в методе logSuspiciousIpActivity: ' . $e->getMessage());
        }
    }

    /**
     * Логирование подозрительной активности по email
     *
     * @param string $email Email пользователя
     * @param int $attempts Количество попыток
     * @param string $ip IP адрес
     * @return void
     */
    protected function logSuspiciousEmailActivity(string $email, int $attempts, string $ip): void
    {
        try {
            Log::channel('security')->warning('Обнаружено множество неудачных попыток для email', [
                'event' => 'suspicious.email',
                'email' => $email,
                'attempts' => $attempts,
                'ip' => $ip,
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (логирование подозрительной активности по email): Ошибка в методе logSuspiciousEmailActivity: ' . $e->getMessage());
        }
    }

    /**
     * Получение страны по IP адресу
     *
     * Метод использует несколько источников для определения страны:
     * 1. Встроенный сервис geoip (если установлен пакет torann/geoip)
     * 2. Внешние API (ip-api.com, ipapi.co)
     * 3. Локальную базу данных
     *
     * @param string $ip IP адрес для определения страны
     * @return string|null Название страны или null при ошибке
     */
    protected function getCountryByIP(string $ip): ?string
    {
        try {
            // Проверка локальных IP адресов
            if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
                return 'local';
            }

            // Проверка приватных IP диапазонов
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return 'Приватная сеть';
            }

            // Способ 1: Использование пакета torann/geoip (если установлен)
            if (function_exists('geoip') && class_exists('Torann\GeoIP\GeoIP')) {
                return $this->getCountryUsingGeoipPackage($ip);
            }

            // Способ 2: Использование внешних API
            $country = $this->getCountryFromExternalAPI($ip);
            if ($country !== null) {
                return $country;
            }

            // Способ 3: Использование локальной базы данных (если настроена)
            return $this->getCountryFromLocalDatabase($ip);

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (получение страны по IP адресу): Ошибка в методе getCountryByIP: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Получение страны с использованием пакета torann/geoip
     *
     * @param string $ip IP адрес
     * @return string|null Название страны или null
     */
    protected function getCountryUsingGeoipPackage(string $ip): ?string
    {
        try {
            $location = geoip($ip);

            // Для torann/geoip используем свойство 'country'
            if (isset($location->country)) {
                return $location->country;
            }

            // Альтернативные способы получения страны
            if (method_exists($location, 'getCountry')) {
                return $location->getCountry();
            }

            if (property_exists($location, 'iso_code')) {
                return $location->iso_code;
            }

            return null;

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (получение страны с использованием пакета torann/geoip): Ошибка в методе getCountryUsingGeoipPackage: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Получение страны из внешнего API
     *
     * @param string $ip IP адрес
     * @return string|null Название страны или null
     */
    protected function getCountryFromExternalAPI(string $ip): ?string
    {
        try {
            // API 1: ip-api.com (бесплатный, до 150 запросов в минуту)
            $response1 = Http::timeout(2)
                ->get("http://ip-api.com/json/{$ip}?fields=country,countryCode,status");

            if ($response1->successful()) {
                $data = $response1->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    return $data['country'] ?? $data['countryCode'] ?? null;
                }
            }

            // API 2: ipapi.co (бесплатный, до 1000 запросов в месяц)
            $response2 = Http::timeout(2)
                ->get("https://ipapi.co/{$ip}/country_name/");

            if ($response2->successful() && $response2->body() !== 'Undefined') {
                $country = trim($response2->body());
                return !empty($country) ? $country : null;
            }

            return null;

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (получение страны из внешнего API): Ошибка в методе getCountryFromExternalAPI: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Получение страны из локальной базы данных
     *
     * @param string $ip IP адрес
     * @return string|null Название страны или null
     */
    protected function getCountryFromLocalDatabase(string $ip): ?string
    {
        try {
            // Проверяем существование локальной базы данных GeoIP
            $databasePath = storage_path('app/geoip/GeoLite2-Country.mmdb');

            if (file_exists($databasePath) && class_exists('MaxMind\Db\Reader')) {
                $reader = new \MaxMind\Db\Reader($databasePath);
                $record = $reader->get($ip);
                $reader->close();

                return $record['country']['names']['en'] ?? $record['country']['iso_code'] ?? null;
            }

            return null;

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (получение страны из локальной базы данных): Ошибка в методе getCountryFromLocalDatabase: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Проверка, является ли исключение связанным с безопасностью
     *
     * @param Throwable $e Объект исключения
     * @return bool true - исключение связано с безопасностью, false - нет
     */
    protected function isSecurityRelatedException(Throwable $e): bool
    {
        try {
            $securityExceptions = [
                \Illuminate\Auth\AuthenticationException::class,
                \Illuminate\Auth\Access\AuthorizationException::class,
                \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class,
                \Illuminate\Http\Exceptions\ThrottleRequestsException::class,
                \Illuminate\Validation\ValidationException::class,
                \Illuminate\Session\TokenMismatchException::class,
            ];

            return in_array(get_class($e), $securityExceptions);

        } catch (Throwable $error) {
            Log::error('SecurityLogServiceProvider (проверка, является ли исключение связанным с безопасностью): Ошибка в методе isSecurityRelatedException: ' . $error->getMessage());
            return false;
        }
    }

    /**
     * Регистрация кастомного логирования
     *
     * Метод настраивает:
     * 1. Middleware для логирования попыток аутентификации
     * 2. Глобальные обработчики исключений для логирования атак
     *
     * @return void
     */
    protected function registerCustomLogging(): void
    {
        try {
            // Регистрация middleware для логирования попыток аутентификации
            $this->registerAuthLoggingMiddleware();

            // Настройка глобального обработчика исключений для логирования атак
            $this->configureGlobalExceptionHandler();

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (регистрация кастомного логирования): Ошибка в методе registerCustomLogging: ' . $e->getMessage());
        }
    }

    /**
     * Регистрация middleware для логирования попыток аутентификации
     *
     * @return void
     */
    protected function registerAuthLoggingMiddleware(): void
    {
        try {
            // Регистрируем псевдоним для middleware
            $this->app['router']->aliasMiddleware('log.auth.attempts', \App\Http\Middleware\LogAuthAttempts::class);

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (регистрация middleware для логирования попыток аутентификации): Ошибка в методе registerAuthLoggingMiddleware: ' . $e->getMessage());
        }
    }

    /**
     * Настройка глобального обработчика исключений для логирования атак
     *
     * @return void
     */
    protected function configureGlobalExceptionHandler(): void
    {
        try {
            // Получаем экземпляр обработчика исключений
            $exceptionHandler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);

            // Проверяем, поддерживает ли обработчик метод reportable
            if (method_exists($exceptionHandler, 'reportable')) {
                $exceptionHandler->reportable(function (Throwable $e) {
                    if ($this->isSecurityRelatedException($e)) {
                        Log::channel('security')->error('Обнаружено исключение связанное с безопасностью', [
                            'exception' => get_class($e),
                            'message' => $e->getMessage(),
                            'ip' => request()->ip(),
                            'url' => request()->fullUrl(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                });
            } else {
                // Альтернативный способ регистрации обработчика исключений
                $this->registerExceptionHandlerFallback();
            }

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (настройка глобального обработчика исключений для логирования атак): Ошибка в методе configureGlobalExceptionHandler: ' . $e->getMessage());
        }
    }

    /**
     * Резервный метод регистрации обработчика исключений
     *
     * Используется, если основной метод недоступен
     *
     * @return void
     */
    protected function registerExceptionHandlerFallback(): void
    {
        try {
            // Регистрируем глобальный обработчик исключений через Event
            Event::listen(\Illuminate\Foundation\Events\ReportException::class, function ($event) {
                if (isset($event->exception) && $this->isSecurityRelatedException($event->exception)) {
                    Log::channel('security')->error('Обнаружено исключение связанное с безопасностью (через fallback)', [
                        'exception' => get_class($event->exception),
                        'message' => $event->exception->getMessage(),
                        'ip' => request()->ip(),
                        'url' => request()->fullUrl(),
                        'trace' => $event->exception->getTraceAsString(),
                    ]);
                }
            });

        } catch (Throwable $e) {
            Log::error('SecurityLogServiceProvider (резервный метод регистрации обработчика исключений): Ошибка в методе registerExceptionHandlerFallback: ' . $e->getMessage());
        }
    }

    /**
     * Регистрация сервисов (не используется в данном провайдере)
     *
     * @return void
     */
    public function register(): void
    {
        // Этот метод может быть использован для регистрации сервисов в контейнере
        // В данном провайдере регистрация не требуется
    }
}
